@extends('layouts.app')
@section('content')
<h1>{{ $type->name }}</h1>
<section class="card"><p>{{ $type->description }}</p>
<p>Categorie: {{ $type->code }} · {{ $type->trashed()?'Gearchiveerd':'Beschikbaar' }}</p>
@foreach($type->rates->sortBy('year') as $rate)<p>Vanaf {{ $rate->year }}: {{ \App\Support\MembershipDates::money($rate->amount_cents) }} per jaar.</p>@endforeach
@unless($type->trashed())<a class="button" href="{{ route('member-types.edit',$type) }}">Wijzigen en tarief aankondigen</a>@endunless
</section><a href="{{ route('member-types.index') }}">Terug naar lidsoorten</a>
@endsection
