<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberRequest;
use App\Models\Member;
use App\Models\MemberType;
use App\Services\MemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => 'nullable|string|max:100', 'type' => 'nullable|integer|exists:member_types,id', 'status' => 'nullable|in:active,inactive', 'archive' => 'nullable|boolean']);
        $query = Member::with(['address', 'memberType']);
        if ($request->boolean('archive')) {
            $query->onlyTrashed();
        }
        if ($q = $data['q'] ?? null) {
            $query->where(fn ($s) => $s->where('first_name', 'like', "%$q%")->orWhere('last_name', 'like', "%$q%")->orWhere('nbvv_number', 'like', "%$q%")->orWhereHas('memberType', fn ($t) => $t->where('name', 'like', "%$q%")));
        }
        if ($type = $data['type'] ?? null) {
            $query->where('member_type_id', $type);
        }
        if (($data['status'] ?? '') === 'active') {
            if ($request->boolean('archive')) {
                $query->whereRaw('1 = 0');
            } else {
                $query->currentlyActive();
            }
        }
        if (($data['status'] ?? '') === 'inactive' && ! $request->boolean('archive')) {
            $query->where(fn ($q) => $q->where('is_active', false)->orWhereDate('starts_at', '>', today())->orWhereDate('ends_at', '<=', today()));
        }

        return view('members.index', ['members' => $query->orderBy('last_name')->paginate(20)->withQueryString(), 'types' => MemberType::withTrashed()->get()]);
    }

    public function create(): View
    {
        return view('members.form', ['member' => new Member, 'types' => MemberType::all()]);
    }

    public function store(MemberRequest $request, MemberService $service): RedirectResponse
    {
        $member = $service->save($request->validated());

        return redirect()->route('members.show', $member)->with('success', 'Lid toegevoegd.');
    }

    public function show(string $member): View
    {
        return view('members.show', ['member' => Member::withTrashed()->with(['address', 'memberType', 'invoices'])->findOrFail($member)]);
    }

    public function edit(Member $member): View
    {
        return view('members.form', ['member' => $member, 'types' => MemberType::all()]);
    }

    public function update(MemberRequest $request, Member $member, MemberService $service): RedirectResponse
    {
        $service->save($request->validated(), $member);

        return redirect()->route('members.show', $member)->with('success', 'Lid bijgewerkt.');
    }

    public function destroy(Member $member, MemberService $service): RedirectResponse
    {
        $service->archive($member);

        return redirect()->route('members.index')->with('success', 'Lid en kweeknummer gearchiveerd.');
    }

    public function restore(string $member, MemberService $service): RedirectResponse
    {
        $service->restore((int) $member);

        return back()->with('success', 'Lid hersteld.');
    }
}
