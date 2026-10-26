<?php

namespace App\Http\Controllers;

use App\Models\MembershipRequest;
use App\Services\RequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function index(): View
    {
        return view('requests.index', ['requests' => MembershipRequest::latest()->paginate(30)]);
    }

    public function approve(Request $request, MembershipRequest $membershipRequest, RequestService $service): RedirectResponse
    {
        $service->approve($membershipRequest, $request->user()->id);

        return back()->with('success', 'Aanvraag verwerkt.');
    }

    public function reject(Request $request, MembershipRequest $membershipRequest): RedirectResponse
    {
        $data = $request->validate(['note' => 'required|string|max:1000']);
        $updated = MembershipRequest::whereKey($membershipRequest->id)->where('status', 'pending')->update(['status' => 'rejected', 'note' => $data['note'], 'processed_by' => $request->user()->id, 'processed_at' => now()]);

        return back()->with('success', $updated ? 'Aanvraag afgewezen en bewaard.' : 'Aanvraag was al verwerkt.');
    }
}
