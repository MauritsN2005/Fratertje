<?php

namespace App\Services;

use App\Events\InvoiceRequested;
use App\Http\Requests\MemberRequest;
use App\Models\Member;
use App\Models\MembershipRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestService
{
    public function approve(MembershipRequest $request, int $user): void
    {
        DB::transaction(function () use ($request, $user) {
            $request = MembershipRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'Deze aanvraag is al verwerkt.']);
            }
            if ($request->kind === 'registration') {
                $data = $request->payload;
                
                $form = new MemberRequest;
                $form->setContainer(app())->setRedirector(app('redirect'));
                $form->merge($data);
                $validator = validator($data, $form->rules(), $form->messages());
                foreach ($form->after() as $callback) {
                    $validator->after($callback);
                }
                $validator->validate();
                $member = app(MemberService::class)->save($data + ['consented_at' => $request->consented_at, 'consent_version' => $request->consent_version]);
                $request->member_id = $member->id;
            } else {
                $member = Member::withTrashed()->whereKey($request->member_id)->lockForUpdate()->firstOrFail();
                if ($member->ends_at) {
                    throw ValidationException::withMessages(['request' => 'Dit lid heeft al een vastgelegde einddatum.']);
                }
                $member->update(['ends_at' => $request->effective_at->max($member->starts_at)]);
                foreach ($member->invoices()->where('kind', 'debit')->get() as $invoice) {
                    event(new InvoiceRequested($member, $invoice->year, 'credit'));
                }
            }
            $request->fill(['status' => 'approved', 'processed_by' => $user, 'processed_at' => now()])->save();
        });
    }
}
