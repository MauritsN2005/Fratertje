<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberType;
use App\Services\MemberService;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demogegevens zijn alleen toegestaan in local/testing.');
        }
        $this->call(DatabaseSeeder::class);
        foreach ([
            ['Noor', 'Veld', 'adult', '1990-05-12', 'DEMO01'],
            ['Sam', 'Bos', 'youth', today()->subYears(14)->toDateString(), 'DEMO02'],
            ['Alex', 'Meer', 'guest', '1985-02-18', null],
        ] as [$first, $last, $code, $birth, $number]) {
            $email = strtolower($first).'@example.test';
            if (Member::withTrashed()->where('email', $email)->exists()) {
                continue;
            }
            app(MemberService::class)->save([
                'first_name' => $first, 'last_name' => $last, 'email' => $email,
                'birth_date' => $birth, 'member_type_id' => MemberType::where('code', $code)->value('id'),
                'nbvv_number' => $number, 'registered_at' => today()->subMonthsNoOverflow(6)->toDateString(),
                'street' => 'Voorbeeldstraat', 'house_number' => '10', 'postal_code' => '1234 AB', 'city' => 'Gouda',
            ]);
        }
    }
}
