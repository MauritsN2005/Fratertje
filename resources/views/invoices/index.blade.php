@extends('layouts.app')

@section('content')<h1>Facturen en creditnota's</h1>
<p>Maak een factuur aan vanuit de detailpagina van een lid. Bedragen worden door het systeem berekend en daarna bewaard.</p>
<div class="table-scroll">
<table>
<thead>
<tr>
<th>Nummer</th>
<th>Lid</th>
<th>Soort</th>
<th>Bedrag</th>
</tr>
</thead>
<tbody>
@forelse($invoices as $invoice)<tr>
<td>
<a href="{{ route('invoices.show',$invoice) }}">{{ $invoice->number }}</a>
</td>
<td>{{ $invoice->snapshot['name'] }}</td>
<td>{{ $invoice->kind==='credit'?'Creditnota':'Factuur' }}</td>
<td>{{ \App\Support\MembershipDates::money($invoice->amount_cents) }}</td>
</tr>
@empty<tr>
<td colspan="4">Nog geen facturen.</td>
</tr>
@endforelse</tbody>
</table>
</div>{{ $invoices->links() }}
@endsection
