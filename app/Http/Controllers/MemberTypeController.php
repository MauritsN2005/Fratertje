<?php

namespace App\Http\Controllers;

use App\Models\MembershipRequest;
use App\Models\MemberType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MemberTypeController extends Controller
{
    public function index(): View
    {
        return view('types.index', ['types' => MemberType::withTrashed()->with('rates')->get()]);
    }

    public function create(): View
    {
        return view('types.form', ['type' => new MemberType]);
    }

    public function edit(MemberType $memberType): View
    {
        return view('types.form', ['type' => $memberType]);
    }

    private function data(Request $request, ?MemberType $type = null): array
    {
        return $request->validate(['name' => 'required|string|max:80', 'description' => 'required|string|max:1000',
            'code' => [$type ? 'prohibited' : 'required', Rule::in(['adult', 'youth', 'guest']), Rule::unique('member_types', 'code')],
            'price' => 'required|decimal:0,2|numeric|min:0|max:9999']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->data($request);
        DB::transaction(function () use ($data) {
            $type = MemberType::create($data);
            $type->rates()->create(['year' => now()->year, 'amount_cents' => (int) round($data['price'] * 100), 'announced_at' => now()]);
        });

        return redirect()->route('member-types.index')->with('success', 'Lidsoort toegevoegd.');
    }

    public function update(Request $request, MemberType $memberType): RedirectResponse
    {
        $data = $this->data($request, $memberType);
        DB::transaction(function () use ($data, $memberType) {
            $memberType->update(['name' => $data['name'], 'description' => $data['description']]);
            $memberType->rates()->updateOrCreate(['year' => now()->year + 1], ['amount_cents' => (int) round($data['price'] * 100), 'announced_at' => now()]);
        });

        return redirect()->route('member-types.index')->with('success', 'Tarief voor volgend jaar opgeslagen en op de openbare tarievenpagina aangekondigd.');
    }

    public function destroy(MemberType $memberType): RedirectResponse
    {
        if ($memberType->members()->withTrashed()->exists() || MembershipRequest::where('status', 'pending')->where('kind', 'registration')->get()->contains(fn ($r) => ($r->payload['member_type_id'] ?? null) == $memberType->id)) {
            throw ValidationException::withMessages(['type' => 'Deze lidsoort is nog gekoppeld aan leden of aanvragen.']);
        }
        $memberType->delete();

        return back()->with('success', 'Lidsoort gearchiveerd.');
    }

    public function restore(string $memberType): RedirectResponse
    {
        MemberType::onlyTrashed()->findOrFail($memberType)->restore();

        return back()->with('success', 'Lidsoort hersteld.');
    }
}
