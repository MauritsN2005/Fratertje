<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberType;
use App\Models\User;
use App\Services\MemberService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RecordingSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') || MemberType::withTrashed()->exists() || Member::withTrashed()->exists()) {
            throw new \RuntimeException('Alleen gebruiken in een lege, afzonderlijke lokale opnamedatabase. Er worden geen bestaande gegevens verwijderd.');
        }
        foreach (['adult' => ['Volwassen lid', 3600], 'youth' => ['Jeugdlid', 1800]] as $code => [$name, $amount]) {
            $type = MemberType::create(['code' => $code, 'name' => $name, 'description' => $name, 'price' => $amount / 100]);
            $type->rates()->create(['year' => 2026, 'amount_cents' => $amount, 'announced_at' => now()]);
        }
        User::firstOrCreate(['email' => 'beheerder@fratertje.test'], ['name' => 'Demo beheerder', 'password' => Hash::make('Fratertje-Demo-2026!')]);
        for ($i = 1; $i <= 21; $i++) {
            app(MemberService::class)->save([
                'first_name' => 'Demo', 'last_name' => 'Voorbeeld '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'email' => 'demo'.$i.'@example.test', 'birth_date' => '1990-01-01',
                'member_type_id' => MemberType::where('code', 'adult')->value('id'),
                'nbvv_number' => 'FILM'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'registered_at' => today()->subYear()->startOfYear()->toDateString(),
                'street' => 'Voorbeeldstraat', 'house_number' => (string) $i, 'postal_code' => '1234 AB', 'city' => 'Teststad',
            ]);
        }
    }
}
