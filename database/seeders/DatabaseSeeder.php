<?php

namespace Database\Seeders;

use App\Models\MemberType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['adult' => ['Volwassen lid', 3600], 'youth' => ['Jeugdlid', 1800], 'guest' => ['Gastlid', 1800]] as $code => [$name,$amount]) {
            $type = MemberType::withTrashed()->firstOrCreate(['code' => $code], ['name' => $name, 'description' => $name, 'price' => $amount / 100]);
            $type->rates()->firstOrCreate(['year' => 2026], ['amount_cents' => $amount, 'announced_at' => now()]);
        }
        if (app()->environment(['local', 'testing'])) {
            User::firstOrCreate(['email' => 'beheerder@fratertje.test'], ['name' => 'Demo beheerder', 'password' => Hash::make('Fratertje-Demo-2026!')]);
        }
    }
}
