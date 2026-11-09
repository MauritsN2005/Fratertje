<?php

use App\Events\InvoiceRequested;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipRequest;
use App\Models\User;
use App\Services\MemberService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-09-29 12:00:00'));
    if (! str_ends_with(config('database.connections.mysql.database'), '_compat_test')) {
        throw new RuntimeException('Deze tests vereisen een afzonderlijke _compat_test-database met de oorspronkelijke zes migrations.');
    }
    expect(Schema::hasColumn('membership_requests', 'consent_statement'))->toBeFalse();
});

it('stores and approves a registration without changing the existing schema', function () {
    $data = memberInput();
    unset($data['registered_at']);

    $this->post(route('registration.store'), $data + ['consent' => 1])->assertSessionHasNoErrors()->assertRedirect(route('home'));

    $request = MembershipRequest::first();
    expect($request->consent_statement)->toBe(config('membership.registration_statement'));
    expect($request->payload['signer_name'])->toBe('Noor Veld');
    $this->assertDatabaseCount('members', 0);
    $this->actingAs(User::first());
    $this->get(route('requests.index'))->assertSee(config('membership.registration_statement'));
    $this->post(route('requests.approve', $request))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('members', ['nbvv_number' => 'AB12']);
    $this->assertDatabaseHas('breeding_numbers', ['breeding_number' => 'AB12']);
});

it('processes cancellation and refund with the existing schema', function () {
    $member = sampleMember();
    event(new InvoiceRequested($member, 2026));
    $url = URL::temporarySignedRoute('cancellation.confirm', now()->addHour(), ['member' => $member->id]);

    $this->post($url, ['consent' => 1])->assertSessionHasNoErrors()->assertRedirect(route('home'));

    $request = MembershipRequest::first();
    expect($request->consent_statement)->toBe(config('membership.cancellation_statement'));
    $this->actingAs(User::first());
    $this->post(route('requests.approve', $request))->assertSessionHasNoErrors();
    expect($member->fresh()->ends_at->toDateString())->toBe('2026-11-01');
    expect(Invoice::where('kind', 'credit')->first()->amount_cents)->toBe(-600);
});

it('archives and restores member address and number without new triggers', function () {
    $member = sampleMember();
    $this->actingAs(User::first());

    $this->delete(route('breeding-numbers.destroy', $member->breedingNumber))->assertSessionHasNoErrors();
    $this->assertSoftDeleted($member);
    app(MemberService::class)->restore($member->id);

    expect(Member::findOrFail($member->id)->address->trashed())->toBeFalse();
    expect($member->fresh()->breedingNumber->breeding_number)->toBe('AB12');
});
