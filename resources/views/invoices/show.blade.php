@extends('layouts.app')

@section('content')<div class="bar">
<h1>{{ $invoice->kind==='credit'?'Creditnota':'Contributiefactuur' }} {{ $invoice->number }}</h1>
<button data-print class="no-print">Afdrukken / opslaan als PDF</button>
</div>
<article class="card">
<p class="eyebrow">Vogelvereniging 't Fratertje</p>
<p>Datum: {{ $invoice->created_at->format('d-m-Y') }}<br>Contributiejaar: {{ $invoice->year }}</p>
<h2>{{ $invoice->snapshot['name'] }}</h2>
<p>{{ $invoice->snapshot['address']['street'] }} {{ $invoice->snapshot['address']['house_number'] }}<br>{{ $invoice->snapshot['address']['postal_code'] }} {{ $invoice->snapshot['address']['city'] }}</p>
<table>
<thead>
<tr>
<th>Omschrijving</th>
<th>Jaarprijs</th>
<th>Maanden</th>
<th>Bedrag</th>
</tr>
</thead>
<tbody>
<tr>
<td>{{ $invoice->snapshot['type'] }} {{ $invoice->kind==='credit'?'— teruggave resterende maanden':'' }}</td>
<td>{{ \App\Support\MembershipDates::money($invoice->annual_cents) }}</td>
<td>{{ $invoice->months }} / 12</td>
<td>{{ \App\Support\MembershipDates::money($invoice->amount_cents) }}</td>
</tr>
</tbody>
</table>
<p class="invoice-total">Totaal {{ \App\Support\MembershipDates::money($invoice->amount_cents) }}</p>@if($invoice->kind==='credit')<p>Deze creditnota legt de teruggave vast. De administratie voert de daadwerkelijke betaling apart uit.</p>
@endif</article>
@endsection
