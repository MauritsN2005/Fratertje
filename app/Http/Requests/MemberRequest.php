<?php

namespace App\Http\Requests;

use App\Models\MemberType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->email)), 'nbvv_number' => $this->nbvv_number ? strtoupper(trim($this->nbvv_number)) : null, 'postal_code' => strtoupper(trim((string) $this->postal_code))]);
    }

    public function rules(): array
    {
        $id = $this->route('member')?->id;

        return [
            'first_name' => ['required', 'string', 'max:80'], 'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:200', Rule::unique('members')->ignore($id)],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after:1900-01-01'],
            'member_type_id' => ['required', 'integer', Rule::exists('member_types', 'id')->whereNull('deleted_at')],
            'nbvv_number' => ['nullable', 'string', 'regex:/^[A-Z0-9-]{2,20}$/', Rule::unique('members', 'nbvv_number')->ignore($id)],
            'street' => ['required', 'string', 'max:120'], 'house_number' => ['required', 'string', 'max:15'],
            'postal_code' => ['required', 'regex:/^[1-9][0-9]{3}\s?[A-Z]{2}$/'], 'city' => ['required', 'string', 'max:100'],
            'registered_at' => [$this->routeIs('registration.store') ? 'prohibited' : 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'signer_name' => [$this->routeIs('registration.store') ? 'required' : 'nullable', 'string', 'max:180'],
            'is_active' => [$this->routeIs('registration.store') ? 'prohibited' : 'sometimes', 'boolean'],
            'consent' => [$this->routeIs('registration.store') ? 'accepted' : 'nullable'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $type = MemberType::find($this->member_type_id);
            $age = CarbonImmutable::parse($this->birth_date)->age;
            if (($type->code === 'adult' && $age < 18) || ($type->code === 'youth' && $age >= 18)) {
                $validator->errors()->add('member_type_id', 'Jeugdlid: jonger dan 18. Volwassen lid: vanaf 18 jaar.');
            }
            if ($type->code === 'guest' && $this->nbvv_number) {
                $validator->errors()->add('nbvv_number', 'Een gastlid mag geen kweeknummer hebben.');
            }
            if ($type->code !== 'guest' && ! $this->nbvv_number) {
                $validator->errors()->add('nbvv_number', 'Een kweeknummer is verplicht voor jeugd- en volwassen leden.');
            }
        }];
    }

    public function messages(): array
    {
        return ['*.required' => 'Dit veld is verplicht.', '*.max' => 'De invoer is te lang.', 'email.unique' => 'Dit e-mailadres is al geregistreerd, eventueel in het archief.', 'nbvv_number.unique' => 'Dit kweeknummer is al geregistreerd, eventueel in het archief.', 'nbvv_number.regex' => 'Gebruik 2–20 letters, cijfers of streepjes.', 'postal_code.regex' => 'Gebruik een Nederlandse postcode, bijvoorbeeld 1234 AB.', 'consent.accepted' => 'Bevestig dat je akkoord gaat met de aanmelding.', 'birth_date.before_or_equal' => 'De geboortedatum mag niet in de toekomst liggen.'];
    }

    public function attributes(): array
    {
        return ['birth_date' => 'geboortedatum', 'email' => 'e-mailadres', 'registered_at' => 'aanmelddatum', 'member_type_id' => 'lidsoort'];
    }
}
