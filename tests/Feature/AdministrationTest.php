<?php

use App\Events\InvoiceRequested;
use App\Mail\CancellationLink;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipRequest;
use App\Models\MemberType;
use App\Models\User;
use App\Services\MemberService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-09-29 12:00:00'));
});
function adminSetup($test): void
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
it('protects administration pages', function ($route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['dashboard', 'members.index', 'members.create', 'member-types.index', 'breeding-numbers.index', 'requests.index', 'invoices.index']);
it('logs in and logs out with hashed credentials', function () {
    $this->seed();
    $this->post('/login', ['email' => 'beheerder@fratertje.test', 'password' => 'Fratertje-Demo-2026!'])->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});
it('rejects invalid credentials', function () {
    $this->seed();
    $this->post('/login', ['email' => 'beheerder@fratertje.test', 'password' => 'bad'])->assertSessionHasErrors('email');
    $this->assertGuest();
});
it('creates one member with an address and matching NBvV number', function () {
    adminSetup($this);
    $this->post(route('members.store'), memberInput() + ['ends_at' => '2026-01-01'])->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('members', ['nbvv_number' => 'AB12', 'is_active' => true, 'ends_at' => null]);
    expect(Member::first()->starts_at->toDateString())->toBe('2026-04-01');
    $this->assertDatabaseCount('addresses', 1);
    $this->assertDatabaseHas('breeding_numbers', ['breeding_number' => 'AB12']);
});
it('rejects missing NBvV numbers and rolls back all member data', function () {
    adminSetup($this);
    $this->post(route('members.store'), memberInput(['nbvv_number' => null]))->assertSessionHasErrors('nbvv_number');
    $this->assertDatabaseCount('members', 0);
    $this->assertDatabaseCount('addresses', 0);
});
it('rejects a number for guest members', function () {
    adminSetup($this);
    $this->post(route('members.store'), memberInput(['member_type_id' => MemberType::where('code', 'guest')->value('id')]))->assertSessionHasErrors('nbvv_number');
});
it('creates a guest without a number', function () {
    adminSetup($this);
    $this->post(route('members.store'), memberInput(['member_type_id' => MemberType::where('code', 'guest')->value('id'), 'nbvv_number' => null]))->assertSessionHasNoErrors();
    $this->assertDatabaseCount('members', 1);
    $this->assertDatabaseCount('breeding_numbers', 0);
});
it('rejects an adult registered as youth', function () {
    adminSetup($this);
    $this->post(route('members.store'), memberInput(['member_type_id' => MemberType::where('code', 'youth')->value('id')]))->assertSessionHasErrors('member_type_id');
});
it('rejects duplicate numbers including archived members', function () {
    adminSetup($this);
    $member = sampleMember();
    app(MemberService::class)->archive($member);
    $this->post(route('members.store'), memberInput(['email' => 'second@example.test']))->assertSessionHasErrors('nbvv_number');
});
it('updates member and address together', function () {
    adminSetup($this);
    $member = sampleMember();
    $this->put(route('members.update', $member), memberInput(['city' => 'Utrecht', 'nbvv_number' => 'ZX99']))->assertSessionHasNoErrors();
    expect($member->fresh()->address->city)->toBe('Utrecht');
    expect($member->fresh()->breedingNumber->breeding_number)->toBe('ZX99');
});
it('archives and restores member address and number', function () {
    adminSetup($this);
    $member = sampleMember();
    $address = $member->address;
    $number = $member->breedingNumber;
    $this->delete(route('members.destroy', $member))->assertRedirect();
    $this->assertSoftDeleted($member);
    $this->assertSoftDeleted($address);
    $this->assertSoftDeleted($number);
    $this->get(route('members.index', ['archive' => 1]))->assertSee('Noor Veld');
    $this->post(route('members.restore', $member->id))->assertSessionHasNoErrors();
    expect($member->fresh()->deleted_at)->toBeNull();
    expect($number->fresh()->deleted_at)->toBeNull();
});
it('filters and searches without exposing unrelated records', function () {
    adminSetup($this);
    sampleMember();
    sampleMember(['email' => 'other@example.test', 'first_name' => 'Els', 'nbvv_number' => 'ZZ22', 'registered_at' => '2026-09-29']);
    $this->get(route('members.index', ['q' => 'AB12', 'status' => 'active']))->assertSee('Noor Veld')->assertDontSee('Els Veld');
});
it('renders every populated administration page', function () {
    adminSetup($this);
    $member = sampleMember();
    foreach (['dashboard', 'members.index', 'members.create', 'member-types.index', 'member-types.create', 'breeding-numbers.index', 'requests.index', 'invoices.index'] as $route) {
        $this->get(route($route))->assertOk();
    }
    $this->get(route('members.show', $member))->assertOk();
    $this->get(route('members.edit', $member))->assertOk();
    $this->get(route('member-types.edit', $member->memberType))->assertOk();
    $this->get(route('breeding-numbers.edit', $member->breedingNumber))->assertOk();
});
it('escapes stored member names', function () {
    adminSetup($this);
    $member = sampleMember(['first_name' => '<script>alert(1)</script>']);
    $this->get(route('members.show', $member))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});
it('quarantines registrations until approval and prevents repeated approval', function () {
    $this->seed();
    $data = memberInput();
    unset($data['registered_at']);
    $this->post(route('registration.store'), $data + ['consent' => 1])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('members', 0);
    $this->assertDatabaseCount('membership_requests', 1);
    $this->actingAs(User::first());
    $request = MembershipRequest::first();
    $this->post(route('requests.approve', $request))->assertSessionHasNoErrors();
    $this->assertDatabaseCount('members', 1);
    expect($request->fresh()->status)->toBe('approved');
    $this->post(route('requests.approve', $request))->assertSessionHasErrors();
    $this->assertDatabaseCount('members', 1);
});
it('requires consent for public registration', function () {
    $this->seed();
    $data = memberInput();
    unset($data['registered_at']);
    $this->post(route('registration.store'), $data)->assertSessionHasErrors('consent');
    $this->assertDatabaseCount('membership_requests', 0);
});
it('requires a valid unexpired cancellation signature', function () {
    $this->seed();
    $member = sampleMember();
    $this->get(route('cancellation.confirm', $member))->assertForbidden();
    $url = URL::temporarySignedRoute('cancellation.confirm', now()->subMinute(), ['member' => $member->id]);
    $this->post($url, ['consent' => 1])->assertForbidden();
    $this->assertDatabaseCount('membership_requests', 0);
});
it('calculates prorated invoices using the event and preserves snapshots', function () {
    adminSetup($this);
    $member = sampleMember();
    $this->post(route('invoices.store', $member), ['year' => 2026, 'amount_cents' => 1])->assertSessionHasNoErrors();
    $invoice = Invoice::first();
    expect($invoice->amount_cents)->toBe(2700);
    expect($invoice->months)->toBe(9);
    $this->get(route('invoices.show', $invoice))->assertSee('27,00');
    $this->post(route('invoices.store', $member), ['year' => 2026]);
    $this->assertDatabaseCount('invoices', 1);
    $member->update(['first_name' => 'Changed']);
    expect($invoice->fresh()->snapshot['name'])->toBe('Noor Veld');
});
it('charges youth rate for a member who turns eighteen on January first', function () {
    adminSetup($this);
    $member = sampleMember(['birth_date' => '2008-01-01']);
    event($event = new InvoiceRequested($member, 2026));
    expect($event->invoice->amount_cents)->toBe(1350);
});
it('announces next year prices without changing current rates', function () {
    adminSetup($this);
    $type = MemberType::where('code', 'adult')->first();
    $this->put(route('member-types.update', $type), ['name' => 'Volwassen lid', 'description' => 'Vanaf 18', 'price' => '48.00'])->assertSessionHasNoErrors();
    expect($type->rateFor(2026))->toBe(3600);
    expect($type->rateFor(2027))->toBe(4800);
    $this->get('/')->assertSee('48,00');
});
it('credits remaining invoiced months once after confirmed cancellation', function () {
    $this->seed();
    $member = sampleMember();
    event(new InvoiceRequested($member, 2026));
    $url = URL::temporarySignedRoute('cancellation.confirm', now()->addHour(), ['member' => $member->id]);
    $this->post($url, ['consent' => 1])->assertSessionHasNoErrors();
    $this->post($url, ['consent' => 1]);
    $this->assertDatabaseCount('membership_requests', 1);
    $this->actingAs(User::first());
    $this->post(route('requests.approve', MembershipRequest::first()))->assertSessionHasNoErrors();
    expect($member->fresh()->ends_at->toDateString())->toBe('2026-11-01');
    expect(Invoice::where('kind', 'credit')->first()->amount_cents)->toBe(-600);
});
it('prevents deletion of a used member type', function () {
    adminSetup($this);
    $member = sampleMember();
    $this->delete(route('member-types.destroy', $member->memberType))->assertSessionHasErrors();
});
it('archives the member when archiving its mandatory number', function () {
    adminSetup($this);
    $member = sampleMember();
    $this->delete(route('breeding-numbers.destroy', $member->breedingNumber))->assertRedirect();
    $this->assertSoftDeleted($member);
});
it('updates breeding numbers with server validation', function () {
    adminSetup($this);
    $member = sampleMember();
    $this->put(route('breeding-numbers.update', $member->breedingNumber), ['breeding_number' => 'CD34', 'issue_year' => 2026])->assertSessionHasNoErrors();
    expect($member->fresh()->nbvv_number)->toBe('CD34');
});
it('converts a youth member on their eighteenth birthday', function () {
    adminSetup($this);
    $member = sampleMember(['birth_date' => '2008-10-01', 'member_type_id' => MemberType::where('code', 'youth')->value('id')]);
    $this->travelTo(Carbon::parse('2026-10-01'));
    $this->artisan('members:age')->assertExitCode(0);
    expect($member->fresh()->membership_code)->toBe('adult');
});

it('sends cancellation mail only to the registered address', function () {
    $this->seed();
    $member = sampleMember();
    Mail::fake();

    $this->post(route('cancellation.send'), ['email' => $member->email])->assertSessionHasNoErrors();

    Mail::assertSent(CancellationLink::class, function ($mail) use ($member) {
        return $mail->hasTo($member->email) && str_contains($mail->confirmationUrl, 'signature=');
    });
});

it('does not disclose whether an email address exists', function () {
    $this->seed();
    Mail::fake();

    $this->post(route('cancellation.send'), ['email' => 'unknown@example.test'])
        ->assertSessionHas('success', 'Als dit e-mailadres bij een lid hoort, is een bevestigingslink verstuurd.');

    Mail::assertNothingSent();
});

it('rejects an altered cancellation signature', function () {
    $this->seed();
    $member = sampleMember();
    $other = sampleMember(['email' => 'other@example.test', 'nbvv_number' => 'ZZ22']);
    $url = URL::temporarySignedRoute('cancellation.confirm', now()->addHour(), ['member' => $member->id]);

    $this->post(str_replace('/'.$member->id.'/bevestigen', '/'.$other->id.'/bevestigen', $url), ['consent' => 1])->assertForbidden();

    $this->assertDatabaseCount('membership_requests', 0);
});

it('allows administrative deactivation without losing the mandatory number', function () {
    adminSetup($this);
    $member = sampleMember();

    $this->put(route('members.update', $member), memberInput(['is_active' => 0]))->assertSessionHasNoErrors();

    expect($member->fresh()->active_now)->toBeFalse();
    expect($member->fresh()->breedingNumber->breeding_number)->toBe('AB12');
});

it('archives and restores an unused category without creating a fourth category', function () {
    adminSetup($this);
    $type = MemberType::where('code', 'guest')->first();
    $this->delete(route('member-types.destroy', $type))->assertSessionHasNoErrors();
    $this->assertSoftDeleted($type);
    $this->post(route('member-types.store'), ['name' => 'Nog een gast', 'description' => 'Dubbel', 'code' => 'guest', 'price' => 18])->assertSessionHasErrors('code');
    $this->post(route('member-types.restore', $type->id))->assertSessionHasNoErrors();
    expect($type->fresh()->deleted_at)->toBeNull();
});

it('rejects a quarantined application with an audit reason', function () {
    $this->seed();
    $data = memberInput();
    unset($data['registered_at']);
    $this->post(route('registration.store'), $data + ['consent' => 1]);
    $this->actingAs(User::first());
    $request = MembershipRequest::first();

    $this->post(route('requests.reject', $request), ['note' => 'Aanmelding ingetrokken'])->assertSessionHasNoErrors();

    expect($request->fresh()->status)->toBe('rejected');
    expect($request->fresh()->note)->toBe('Aanmelding ingetrokken');
    $this->assertDatabaseCount('members', 0);
});

it('rejects duplicate data discovered during quarantine approval', function () {
    $this->seed();
    $data = memberInput();
    unset($data['registered_at']);
    $this->post(route('registration.store'), $data + ['consent' => 1]);
    sampleMember();
    $this->actingAs(User::first());

    $this->post(route('requests.approve', MembershipRequest::first()))->assertSessionHasErrors('email');

    $this->assertDatabaseCount('members', 1);
    expect(MembershipRequest::first()->status)->toBe('pending');
});

it('refunds the full invoiced amount when cancellation is before membership starts', function () {
    adminSetup($this);
    $member = sampleMember(['registered_at' => '2026-09-29']);
    event($debit = new InvoiceRequested($member, 2026));
    $member->update(['ends_at' => '2026-11-01']);

    event($credit = new InvoiceRequested($member, 2026, 'credit'));

    expect($debit->invoice->amount_cents)->toBe(600);
    expect($credit->invoice->amount_cents)->toBe(-600);
});

it('does not refund a year after which the membership ends', function () {
    adminSetup($this);
    $member = sampleMember();
    event(new InvoiceRequested($member, 2026));
    $member->update(['ends_at' => '2027-01-01']);

    event($credit = new InvoiceRequested($member, 2026, 'credit'));

    expect($credit->invoice)->toBeNull();
    $this->assertDatabaseCount('invoices', 1);
});

it('uses the adult rate in the year after the eighteenth birthday', function () {
    adminSetup($this);
    $member = sampleMember(['birth_date' => '2007-12-31']);

    event($event = new InvoiceRequested($member, 2026));

    expect($event->invoice->annual_cents)->toBe(3600);
});

it('enforces the NBvV requirement directly in MySQL', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('MySQL CHECK constraint: run the MySQL test profile.');
    }
    adminSetup($this);
    $member = sampleMember();

    expect(fn () => DB::table('members')->where('id', $member->id)->update(['nbvv_number' => null]))
        ->toThrow(QueryException::class);
});
