@extends('layouts.app')

@section('content')<section class="hero">
<p class="eyebrow">Vogelvereniging 't Fratertje</p>
<h1>Een vereniging voor elke vogelliefhebber.</h1>
<p>Word lid en regel je aanmelding eenvoudig online. Onze administratie controleert je gegevens en verwerkt je lidmaatschap.</p>
<a class="button" href="{{ route('registration.create') }}">Lid worden</a>
</section>
<h2>Lidmaatschap en contributie</h2>
<div class="grid">
@foreach($types as $type)<section class="card">
<h2>{{ $type->name }}</h2>
<div class="stat">{{ \App\Support\MembershipDates::money($type->rateFor(now()->year)) }}</div>
<p>per jaar · {{ $type->code==='guest'?'Zonder NBvV-lidmaatschap':'Inclusief NBvV-lidmaatschap' }}</p>
@foreach($type->rates->where('year','>',now()->year) as $rate)<p class="notice">Aankondiging: vanaf {{ $rate->year }} bedraagt de contributie {{ \App\Support\MembershipDates::money($rate->amount_cents) }} per jaar.</p>
@endforeach</section>
@endforeach</div>
<p>Je lidmaatschap begint op de eerste dag van de maand na je aanmelddatum plus 21 dagen. Je betaalt alleen voor de resterende maanden. In het jaar waarin je 18 wordt, blijft het jeugdtarief gelden. Voor afmelding geldt dezelfde termijn.</p>
@endsection
