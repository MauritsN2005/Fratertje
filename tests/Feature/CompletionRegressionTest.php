<?php

use App\Events\InvoiceRequested;
use App\Models\BreedingNumber;
use App\Models\Member;
use App\Models\MembershipRequest;
use App\Models\MemberType;
use App\Models\User;
use App\Services\MemberService;
use Carbon\Carbon;
use Database\Seeders\RecordingSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-09-29 12:00:00'));
});

it('restores number member and address from the number screen', function () {
    adminSetup($this);
    $member = sampleMember();
    $number = $member->breedingNumber;
    app(MemberService::class)->archive($member);

    $this->post(route('breeding-numbers.restore', $number->id))->assertSessionHasNoErrors();

    expect($member->fresh()->trashed())->toBeFalse();
    expect($number->fresh()->trashed())->toBeFalse();
    expect($member->fresh()->address->trashed())->toBeFalse();
});

it('restores an archived youth as adult after the eighteenth birthday', function () {
    adminSetup($this);
    $member = sampleMember(['birth_date' => '2008-10-01', 'member_type_id' => MemberType::where('code', 'youth')->value('id')]);
    app(MemberService::class)->archive($member);
    $this->travelTo(Carbon::parse('2026-10-01'));

    $this->post(route('members.restore', $member->id))->assertSessionHasNoErrors();

    expect($member->fresh()->membership_code)->toBe('adult');
    event($event = new InvoiceRequested($member->fresh(), 2026));
    expect($event->invoice->annual_cents)->toBe(1800);
});

it('blocks restoration when the reserved number does not match', function () {
    adminSetup($this);
    $member = sampleMember();
    app(MemberService::class)->archive($member);
    DB::table('breeding_numbers')->where('member_id', $member->id)->delete();

    $this->post(route('members.restore', $member->id))->assertSessionHasErrors('member');

    $this->assertSoftDeleted($member);
    $this->assertSoftDeleted($member->fresh()->address);
});

it('rejects invalid fields without creating related data', function (array $change, string $field) {
    adminSetup($this);

    $this->post(route('members.store'), memberInput($change))->assertSessionHasErrors($field);

    $this->assertDatabaseCount('members', 0);
    $this->assertDatabaseCount('addresses', 0);
})->with([
    'empty name' => [['first_name' => ''], 'first_name'],
    'long name' => [['last_name' => str_repeat('a', 101)], 'last_name'],
    'invalid email' => [['email' => 'geen-email'], 'email'],
    'future birth' => [['birth_date' => '2027-01-01'], 'birth_date'],
    'invalid date' => [['birth_date' => '2026-02-30'], 'birth_date'],
    'missing street' => [['street' => ''], 'street'],
    'invalid postcode' => [['postal_code' => '0000 AA'], 'postal_code'],
    'long house number' => [['house_number' => str_repeat('1', 16)], 'house_number'],
    'unknown type' => [['member_type_id' => 999999], 'member_type_id'],
    'invalid number' => [['nbvv_number' => 'a b!'], 'nbvv_number'],
    'inactive adult still needs number' => [['nbvv_number' => null, 'is_active' => 0], 'nbvv_number'],
]);

it('requires a number for a youth before activation', function () {
    adminSetup($this);

    $this->post(route('members.store'), memberInput(['birth_date' => '2010-01-01', 'member_type_id' => MemberType::where('code', 'youth')->value('id'), 'nbvv_number' => null, 'is_active' => 0]))->assertSessionHasErrors('nbvv_number');

    $this->assertDatabaseCount('members', 0);
});

it('allows a currently active guest with no number', function () {
    adminSetup($this);

    $this->post(route('members.store'), memberInput(['member_type_id' => MemberType::where('code', 'guest')->value('id'), 'nbvv_number' => null, 'is_active' => 1]))->assertSessionHasNoErrors();

    expect(Member::first()->active_now)->toBeTrue();
    $this->assertDatabaseCount('breeding_numbers', 0);
});

it('uses adult membership on the eighteenth birthday while retaining youth billing', function () {
    adminSetup($this);

    $this->post(route('members.store'), memberInput(['birth_date' => '2008-09-29']))->assertSessionHasNoErrors();

    expect(Member::first()->membership_code)->toBe('adult');
    event($event = new InvoiceRequested(Member::first(), 2026));
    expect($event->invoice->annual_cents)->toBe(1800);
});

it('preserves combined filters through pagination and reports empty results', function () {
    adminSetup($this);
    for ($i = 1; $i <= 21; $i++) {
        sampleMember(['email' => "demo{$i}@example.test", 'nbvv_number' => "PAG{$i}"]);
    }
    $type = MemberType::where('code', 'adult')->first();

    $response = $this->get(route('members.index', ['q' => 'Noor Veld', 'type' => $type->id, 'status' => 'active']));

    $response->assertSee('Noor Veld')->assertSee('page=2', false)->assertSee('status=active', false)->assertSee('q=Noor', false);
    $this->get(route('members.index', ['q' => 'Onvindbaar', 'type' => $type->id, 'status' => 'active']))->assertSee('Geen leden gevonden.');
});

it('keeps invoices and historical rates unchanged after next year announcement', function () {
    adminSetup($this);
    $member = sampleMember();
    event($event = new InvoiceRequested($member, 2026));
    $snapshot = $event->invoice->fresh()->toArray();

    $this->put(route('member-types.update', $member->memberType), ['name' => 'Volwassen lid', 'description' => 'Vanaf 18', 'price' => 48])->assertSessionHasNoErrors();

    expect($event->invoice->fresh()->toArray())->toBe($snapshot);
    expect($member->memberType->rateFor(2026))->toBe(3600);
});

it('adds a missing prescribed category at its fixed base rate', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('member-types.store'), ['code' => 'adult', 'name' => 'Volwassen lid', 'description' => 'Vanaf 18', 'price' => 18])->assertSessionHasNoErrors();

    expect(MemberType::first()->rateFor(2026))->toBe(3600);
    $this->get(route('member-types.show', MemberType::first()))->assertSee('36,00');
});

it('blocks protected pages after logout and rejects unauthenticated writes', function () {
    adminSetup($this);
    $member = sampleMember();
    $this->post(route('logout'));

    $this->get(route('members.show', $member))->assertRedirect(route('login'));
    $this->delete(route('members.destroy', $member))->assertRedirect(route('login'));

    $this->assertNotSoftDeleted($member);
});

it('enforces the existing MySQL membership and number constraints', function () {
    adminSetup($this);
    $member = sampleMember();
    $number = $member->breedingNumber;

    expect(fn () => DB::table('members')->where('id', $member->id)->update(['membership_code' => 'guest', 'member_type_id' => MemberType::where('code', 'guest')->value('id')]))->toThrow(QueryException::class);
    expect(fn () => DB::table('members')->where('id', $member->id)->update(['member_type_id' => 999999]))->toThrow(QueryException::class);
    expect(fn () => DB::table('breeding_numbers')->where('id', $number->id)->update(['breeding_number' => 'OTHER01']))->toThrow(QueryException::class);
})->skip(fn () => config('database.default') !== 'mysql', 'Requires the existing MySQL CHECK constraints.');

it('rejects a duplicate number update without changing either member', function () {
    adminSetup($this);
    $first = sampleMember();
    $second = sampleMember(['email' => 'second@example.test', 'nbvv_number' => 'CD34']);

    $this->put(route('breeding-numbers.update', $second->breedingNumber), ['breeding_number' => 'AB12', 'issue_year' => 2026])->assertSessionHasErrors('breeding_number');

    expect($first->fresh()->nbvv_number)->toBe('AB12');
    expect($second->fresh()->nbvv_number)->toBe('CD34');
    expect($second->fresh()->breedingNumber->breeding_number)->toBe('CD34');
});

it('retains the precise declaration and signer when consent text changes later', function () {
    $this->seed();
    $data = memberInput();
    unset($data['registered_at']);
    $statement = config('membership.registration_statement');

    $this->post(route('registration.store'), $data + ['consent' => 1])->assertSessionHasNoErrors();
    config(['membership.registration_statement' => 'Een nieuwe verklaring']);

    $request = MembershipRequest::first();
    expect($request->consent_statement)->toBe($statement);
    expect($request->payload['signer_name'])->toBe('Noor Veld');
    $this->assertDatabaseCount('members', 0);
    $this->actingAs(User::first());
    $this->get(route('dashboard'))->assertSee('1');
    $this->get(route('requests.index'))->assertSee($statement);
});

it('blocks restoration of an archived category without changing its related data', function () {
    adminSetup($this);
    $member = sampleMember();
    app(MemberService::class)->archive($member);
    $member->memberType->delete();

    $this->post(route('members.restore', $member->id))->assertSessionHasErrors('member');

    $this->assertSoftDeleted($member);
    $this->assertSoftDeleted($member->fresh()->address);
    $this->assertSoftDeleted(BreedingNumber::withTrashed()->where('member_id', $member->id)->first());
});

it('bills the confirmed month boundary with the prescribed base price', function (string $date, int $months, int $cents) {
    adminSetup($this);
    $member = sampleMember(['registered_at' => $date]);

    $this->post(route('invoices.store', $member), ['year' => 2026])->assertSessionHasNoErrors();

    expect($member->invoices()->first()->months)->toBe($months);
    expect($member->invoices()->first()->amount_cents)->toBe($cents);
})->with([
    '5 March begins 1 April' => ['2026-03-05', 9, 2700],
    '25 March begins 1 May' => ['2026-03-25', 8, 2400],
    '5 May begins 1 June' => ['2026-05-05', 7, 2100],
]);

it('prepares two categories for recording and adds the missing third through administration', function () {
    $this->app->detectEnvironment(fn () => 'local');
    $this->seed(RecordingSeeder::class);
    $this->app->detectEnvironment(fn () => 'testing');
    $this->actingAs(User::first());
    $this->assertDatabaseCount('member_types', 2);

    $this->post(route('member-types.store'), ['code' => 'guest', 'name' => 'Gastlid', 'description' => 'Alle leeftijden, zonder NBvV'])->assertSessionHasNoErrors()->assertRedirect(route('member-types.index'));

    $this->assertDatabaseCount('member_types', 3);
    expect(MemberType::where('code', 'guest')->first()->rateFor(2026))->toBe(1800);
    $this->app->detectEnvironment(fn () => 'local');
    expect(fn () => $this->seed(RecordingSeeder::class))->toThrow(RuntimeException::class);
    $this->app->detectEnvironment(fn () => 'testing');
    $this->assertDatabaseCount('members', 21);
});
