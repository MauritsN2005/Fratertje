<?php

namespace App\Http\Controllers;

use App\Events\InvoiceRequested;
use App\Models\Invoice;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        return view('invoices.index', ['invoices' => Invoice::latest()->paginate(30)]);
    }

    public function store(Request $request, Member $member): RedirectResponse
    {
        $data = $request->validate(['year' => 'required|integer|min:2026|max:'.now()->year]);
        $event = new InvoiceRequested($member, (int) $data['year']);
        event($event);

        return redirect()->route('invoices.show', $event->invoice)->with('success', 'Factuur aangemaakt (of bestaande factuur geopend).');
    }

    public function show(Invoice $invoice): View
    {
        return view('invoices.show', compact('invoice'));
    }
}
