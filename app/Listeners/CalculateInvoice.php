<?php

namespace App\Listeners;

use App\Events\InvoiceRequested;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MemberType;
use App\Support\MembershipDates;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalculateInvoice
{
    public function handle(InvoiceRequested $event): void
    {
        $event->invoice = DB::transaction(function () use ($event) {
            $member = Member::withTrashed()->lockForUpdate()->findOrFail($event->member->id);
            if ($existing = $member->invoices()->where('year', $event->year)->where('kind', $event->kind)->first()) {
                return $existing;
            }
            $year = $event->year;
            $start = CarbonImmutable::create($year, 1, 1)->startOfDay();
            $end = $start->addYear();
            $from = $member->starts_at->toImmutable()->max($start);
            $until = $member->ends_at ? $member->ends_at->toImmutable()->min($end) : $end;
            if ($event->kind === 'credit') {
                $debit = $member->invoices()->where('year', $year)->where('kind', 'debit')->first();
                if (! $debit || ! $member->ends_at || $member->ends_at->gte($end)) {
                    return null;
                }
                $months = (int) $member->ends_at->toImmutable()->max($from)->diffInMonths($end, false);
                $months = max(0, min($debit->months, $months));
                if (! $months) {
                    return null;
                }
                $annual = $debit->annual_cents;
                
                $amount = -($debit->amount_cents - (int) round($annual * ($debit->months - $months) / 12));
                $snapshot = $debit->snapshot;
            } else {
                if ($from->gte($until)) {
                    throw ValidationException::withMessages(['year' => 'Het lid heeft geen contributiemaanden in dit jaar.']);
                }
                $months = (int) $from->diffInMonths($until);
                $code = $member->membership_code === 'guest' ? 'guest' : (MembershipDates::youthRate($member->birth_date->toDateString(), $year) ? 'youth' : 'adult');
                $type = MemberType::withTrashed()->where('code', $code)->firstOrFail();
                $annual = $type->rateFor($year);
                $amount = (int) round($annual * $months / 12);
                $snapshot = ['name' => $member->full_name, 'email' => $member->email, 'address' => $member->address->only(['street', 'house_number', 'postal_code', 'city']), 'type' => $type->name, 'starts_at' => $from->toDateString(), 'ends_at' => $until->toDateString()];
            }

            return Invoice::create(['member_id' => $member->id, 'year' => $year, 'kind' => $event->kind, 'annual_cents' => $annual, 'months' => $months, 'amount_cents' => $amount, 'snapshot' => $snapshot]);
        });
    }
}
