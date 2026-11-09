<?php

use App\Models\Member;
use App\Models\MemberType;
use App\Models\User;
use App\Services\MemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature', 'Compatibility');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function adminSetup(TestCase $test): void
{
    $test->seed();
    $test->actingAs(User::factory()->create());
}

function memberInput(array $overrides = []): array
{
    return array_replace(['first_name' => 'Noor', 'last_name' => 'Veld', 'email' => 'noor@example.test', 'birth_date' => '1990-05-12', 'member_type_id' => MemberType::where('code', 'adult')->value('id'), 'nbvv_number' => 'AB12', 'street' => 'Vogelstraat', 'house_number' => '8', 'postal_code' => '1234 AB', 'city' => 'Gouda', 'registered_at' => '2026-03-05', 'signer_name' => 'Noor Veld'], $overrides);
}

function sampleMember(array $overrides = []): Member
{
    return app(MemberService::class)->save(memberInput($overrides));
}
