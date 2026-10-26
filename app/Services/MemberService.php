<?php

namespace App\Services;

use App\Models\Address;
use App\Models\BreedingNumber;
use App\Models\Member;
use App\Models\MemberType;
use App\Support\MembershipDates;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MemberService
{
    public function save(array $data, ?Member $member = null): Member
    {
        return DB::transaction(function () use ($data, $member) {
            if ($member) {
                $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            }
            $type = MemberType::findOrFail($data['member_type_id']);
            $age = CarbonImmutable::parse($data['birth_date'])->age;
            if (($type->code === 'adult' && $age < 18) || ($type->code === 'youth' && $age >= 18) || ($type->code === 'guest' && ! empty($data['nbvv_number'])) || ($type->code !== 'guest' && empty($data['nbvv_number']))) {
                throw ValidationException::withMessages(['member_type_id' => 'Lidsoort, leeftijd en kweeknummer passen niet bij elkaar.']);
            }
            if ($member && $member->invoices()->exists() && ($member->birth_date->toDateString() !== $data['birth_date'] || $member->registered_at->toDateString() !== $data['registered_at'] || $member->member_type_id != $type->id)) {
                throw ValidationException::withMessages(['member_type_id' => 'Na facturatie zijn geboortedatum, aanmelddatum en lidsoort geblokkeerd. Archiveer het lid of overleg over een administratieve correctie.']);
            }
            if ($member && $member->membership_code !== $type->code && $type->code === 'guest') {
                throw ValidationException::withMessages(['member_type_id' => 'Een bestaand NBvV-lid omzetten naar gastlid vereist een nieuwe inschrijving, zodat het kweeknummer bewaard blijft.']);
            }
            $addressData = Arr::only($data, ['street', 'house_number', 'postal_code', 'city']);
            $address = $member?->address ?? new Address;
            $address->fill($addressData)->save();
            $attributes = Arr::only($data, ['first_name', 'last_name', 'email', 'birth_date', 'registered_at', 'nbvv_number', 'consented_at', 'consent_version']);
            $attributes += ['member_type_id' => $type->id, 'membership_code' => $type->code, 'address_id' => $address->id, 'starts_at' => MembershipDates::effective($data['registered_at']), 'is_active' => $data['is_active'] ?? $member?->is_active ?? true];
            $member ??= new Member;
            $member->fill($attributes)->save();
            if ($type->code !== 'guest') {
                $number = BreedingNumber::withTrashed()->firstOrNew(['member_id' => $member->id]);
                $number->fill(['breeding_number' => $data['nbvv_number'], 'issue_year' => $number->issue_year ?? now()->year, 'deleted_at' => null])->save();
            }

            return $member;
        });
    }

    public function archive(Member $member): void
    {
        DB::transaction(function () use ($member) {
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $member->breedingNumber?->delete();
            $member->address->delete();
            $member->delete();
        });
    }

    public function restore(int $id): void
    {
        DB::transaction(function () use ($id) {
            $member = Member::onlyTrashed()->lockForUpdate()->findOrFail($id);
            if ($member->memberType->trashed()) {
                throw ValidationException::withMessages(['member' => 'Herstel eerst de lidsoort.']);
            }
            $member->address->restore();
            BreedingNumber::onlyTrashed()->where('member_id', $id)->get()->each->restore();
            $member->restore();
        });
    }
}
