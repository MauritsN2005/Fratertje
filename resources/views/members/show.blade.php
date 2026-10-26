@extends('layouts.app')

@section('content')<div class="bar">
<div>
<p class="eyebrow">Lid #{{ $member->id }}</p>
<h1>{{ $member->full_name }}</h1>
</div>
<div class="actions">@if($member->trashed())<form method="post" action="{{ route('members.restore',$member->id) }}">
@csrf<button>Herstellen</button>
</form>
@else<a class="button" href="{{ route('members.edit',$member) }}">Wijzigen</a>
<form method="post" action="{{ route('members.destroy',$member) }}" data-confirm="Dit lid, het adres en kweeknummer archiveren? Facturen blijven bewaard. Dit is geen afmelding of restitutie.">
@csrf 
@method('DELETE')<button class="danger">Archiveren</button>
</form>
@endif</div>
</div>
<div class="grid">
<section class="card">
<h2>Contact</h2>
<p>{{ $member->email }}<br>Geboren {{ $member->birth_date->format('d-m-Y') }}</p>
<p>{{ $member->address->street }} {{ $member->address->house_number }}<br>{{ $member->address->postal_code }} {{ $member->address->city }}</p>
</section>
<section class="card">
<h2>Lidmaatschap</h2>
<dl>
<dt>Lidsoort</dt>
<dd>{{ $member->memberType->name }}</dd>
<dt>Kweeknummer</dt>
<dd>{{ $member->nbvv_number??'Geen (gastlid)' }}</dd>
<dt>Status</dt>
<dd>{{ $member->trashed()?'Gearchiveerd':($member->active_now?'Actief':'Niet actief') }}</dd>
<dt>Aangemeld</dt>
<dd>{{ $member->registered_at->format('d-m-Y') }}</dd>
<dt>Ingang</dt>
<dd>{{ $member->starts_at->format('d-m-Y') }}</dd>
<dt>Einde (exclusief)</dt>
<dd>{{ $member->ends_at?->format('d-m-Y')??'Geen einddatum' }}</dd>
</dl>
</section>
</div>
<section class="card">
<h2>Facturen en teruggave</h2>
@unless($member->trashed())<form class="filters no-print" action="{{ route('invoices.store',$member) }}" method="post">
@csrf<div class="field">
<label for="year">Factuurjaar</label>
<input id="year" type="number" min="2026" max="{{ now()->year }}" name="year" value="{{ now()->year }}" required>
</div>
<button>Factuur aanmaken</button>
</form>
@endunless 
@forelse($member->invoices as $invoice)<p>
<a href="{{ route('invoices.show',$invoice) }}">{{ $invoice->number }} · {{ $invoice->kind==='credit'?'Creditnota':'Factuur' }} · {{ \App\Support\MembershipDates::money($invoice->amount_cents) }}</a>
</p>
@empty<p>Nog geen facturen.</p>
@endforelse</section>
@endsection
