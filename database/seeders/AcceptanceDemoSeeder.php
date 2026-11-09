<?php

namespace Database\Seeders;

use App\Events\InvoiceRequested;
use App\Mail\CancellationLink;
use App\Models\Member;
use App\Models\MembershipRequest;
use App\Models\MemberType;
use App\Models\User;
use App\Services\MemberService;
use App\Services\RequestService;
use App\Support\MembershipDates;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class AcceptanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || config('mail.default') !== 'log') {
            throw new \RuntimeException('Deze fictieve dataset vereist local/testing en de lokale logmailer.');
        }
        $this->call(DemoSeeder::class);
        $service = app(MemberService::class);
        foreach ([
            ['Maart', 'adult', '1990-01-01', 'MAART01'],
            ['Achttien', 'adult', '2008-01-01', 'JARIG01'],
            ['Teruggave', 'adult', '1980-01-01', 'CREDIT01'],
            ['Archief', 'adult', '1992-02-02', 'ARCHIEF01'],
        ] as [$name, $code, $birth, $number]) {
            $email = strtolower($name).'@example.test';
            $member = Member::withTrashed()->where('email', $email)->first();
            if (! $member) {
                $member = $service->save([
                    'first_name' => 'Demo', 'last_name' => $name, 'email' => $email,
                    'birth_date' => $birth, 'member_type_id' => MemberType::where('code', $code)->value('id'),
                    'nbvv_number' => $number, 'registered_at' => '2026-03-05',
                    'street' => 'Voorbeeldstraat', 'house_number' => '10', 'postal_code' => '1234 AB', 'city' => 'Teststad',
                ]);
            }
            if ($name !== 'Archief') {
                event(new InvoiceRequested($member, 2026));
            } elseif (! $member->trashed()) {
                $service->archive($member);
            }
            if ($name === 'Teruggave' && ! MembershipRequest::where('member_id', $member->id)->exists()) {
                $request = MembershipRequest::create([
                    'kind' => 'cancellation', 'member_id' => $member->id, 'payload' => ['name' => $member->full_name],
                    'requested_at' => today(), 'effective_at' => MembershipDates::effective(today()->toDateString()),
                    'consented_at' => now(), 'consent_version' => config('membership.consent_version'),
                    'consent_statement' => config('membership.cancellation_statement'),
                ]);
                Mail::to($member->email)->send(new CancellationLink(URL::temporarySignedRoute('cancellation.confirm', now()->addHour(), ['member' => $member->id])));
                app(RequestService::class)->approve($request, User::first()->id);
            }
        }
        foreach (['pending', 'rejected'] as $status) {
            if (MembershipRequest::where('payload->email', $status.'@example.test')->exists()) {
                continue;
            }
            MembershipRequest::create([
                'kind' => 'registration', 'status' => $status,
                'payload' => ['first_name' => 'Aanvrager', 'last_name' => 'Voorbeeld '.$status, 'email' => $status.'@example.test',
                    'birth_date' => '1990-01-01', 'member_type_id' => MemberType::where('code', 'guest')->value('id'), 'nbvv_number' => null,
                    'registered_at' => today()->toDateString(), 'street' => 'Demostraat', 'house_number' => '2', 'postal_code' => '1234 AB', 'city' => 'Teststad', 'signer_name' => 'Aanvrager Voorbeeld'],
                'requested_at' => today(), 'effective_at' => MembershipDates::effective(today()->toDateString()),
                'consented_at' => now(), 'consent_version' => config('membership.consent_version'), 'consent_statement' => config('membership.registration_statement'),
                'processed_by' => $status === 'rejected' ? User::first()->id : null,
                'processed_at' => $status === 'rejected' ? now() : null,
                'note' => $status === 'rejected' ? 'Fictief afgewezen voorbeeld voor de demonstratie.' : null,
            ]);
        }
    }
}
