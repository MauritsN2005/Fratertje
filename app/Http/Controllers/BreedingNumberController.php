<?php

namespace App\Http\Controllers;

use App\Models\BreedingNumber;
use App\Models\Member;
use App\Services\MemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BreedingNumberController extends Controller
{
    public function index(): View
    {
        return view('numbers.index', ['numbers' => BreedingNumber::withTrashed()->with('member')->orderBy('breeding_number')->paginate(30)]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('members.create')->with('success', 'Registreer het kweeknummer samen met het lid; een NBvV-lid kan niet zonder nummer bestaan.');
    }

    public function show(BreedingNumber $breedingNumber): RedirectResponse
    {
        return redirect()->route('members.show', $breedingNumber->member_id);
    }

    public function edit(BreedingNumber $breedingNumber): View
    {
        return view('numbers.form', ['number' => $breedingNumber]);
    }

    public function update(Request $request, BreedingNumber $breedingNumber): RedirectResponse
    {
        $request->merge(['breeding_number' => strtoupper(trim((string) $request->breeding_number))]);
        $data = $request->validate(['breeding_number' => ['required', 'regex:/^[A-Z0-9-]{2,20}$/', Rule::unique('breeding_numbers')->ignore($breedingNumber->id), Rule::unique('members', 'nbvv_number')->ignore($breedingNumber->member_id)], 'issue_year' => 'required|integer|min:1900|max:'.now()->year]);
        DB::transaction(function () use ($data, $breedingNumber) {
            $member = Member::whereKey($breedingNumber->member_id)->lockForUpdate()->firstOrFail();
            $member->update(['nbvv_number' => $data['breeding_number']]);
            $breedingNumber->update($data);
        });

        return redirect()->route('breeding-numbers.index')->with('success', 'Kweeknummer gewijzigd.');
    }

    public function destroy(BreedingNumber $breedingNumber, MemberService $service): RedirectResponse
    {
        $service->archive($breedingNumber->member);

        return back()->with('success', 'Kweeknummer en bijbehorend lid gearchiveerd; herstel via het ledenarchief.');
    }
}
