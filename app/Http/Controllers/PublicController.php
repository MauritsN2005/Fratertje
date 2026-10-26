<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberRequest;
use App\Mail\CancellationLink;
use App\Models\Member;
use App\Models\MembershipRequest;
use App\Models\MemberType;
use App\Support\MembershipDates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function home(): View
    {
        return view('public.home', ['types' => MemberType::with('rates')->get()]);
    }

    public function create(): View
    {
        return view('public.register', ['member' => new Member, 'types' => MemberType::all()]);
    }

    public function store(MemberRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('consent');
        $data['registered_at'] = today()->toDateString();
        MembershipRequest::create(['kind' => 'registration', 'payload' => $data, 'requested_at' => today(), 'effective_at' => MembershipDates::effective($data['registered_at']), 'consented_at' => now(), 'consent_version' => '2026-09-v1']);

        return redirect()->route('home')->with('success', 'Je aanmelding is ontvangen. De administratie controleert je gegevens.');
    }

    public function cancellation(): View
    {
        return view('public.cancel');
    }

    public function sendCancellation(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email|max:200']);
        $member = Member::where('email', strtolower($data['email']))->whereNull('ends_at')->first();
        if ($member) {
            $url = URL::temporarySignedRoute('cancellation.confirm', now()->addHour(), ['member' => $member->id]);
            Mail::to($member->email)->send(new CancellationLink($url));
        }

        return back()->with('success', 'Als dit e-mailadres bij een lid hoort, is een bevestigingslink verstuurd.');
    }

    public function confirm(Member $member): View
    {
        return view('public.confirm', ['member' => $member]);
    }

    public function cancel(Request $request, Member $member): RedirectResponse
    {
        $request->validate(['consent' => 'accepted']);
        DB::transaction(function () use ($member) {
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            if ($member->ends_at || MembershipRequest::where('member_id', $member->id)->where('kind', 'cancellation')->where('status', 'pending')->exists()) {
                return;
            }
            MembershipRequest::create(['kind' => 'cancellation', 'member_id' => $member->id, 'payload' => ['name' => $member->full_name], 'requested_at' => today(), 'effective_at' => MembershipDates::effective(today()->toDateString()), 'consented_at' => now(), 'consent_version' => '2026-09-v1']);
        });

        return redirect()->route('home')->with('success', 'Je afmelding is opgeslagen. De administratie verwerkt de einddatum en eventuele teruggave.');
    }
}
