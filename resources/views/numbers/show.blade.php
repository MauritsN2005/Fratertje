@extends('layouts.app')
@section('content')
<h1>Kweeknummer {{ $number->breeding_number }}</h1>
<section class="card"><dl>
<dt>Gekoppeld lid</dt><dd><a href="{{ route('members.show',$number->member_id) }}">{{ $number->member->full_name }}</a></dd>
<dt>Uitgiftejaar</dt><dd>{{ $number->issue_year }}</dd>
<dt>Status</dt><dd>{{ $number->trashed()?'Gearchiveerd':'Beschikbaar bij het gekoppelde lid' }}</dd>
</dl><p>Dit nummer is tevens het NBvV-lidnummer. Het blijft uniek in het archief.</p>
@if($number->trashed())<form method="post" action="{{ route('breeding-numbers.restore',$number->id) }}">@csrf<button>Nummer en lid herstellen</button></form>
@else<a class="button" href="{{ route('breeding-numbers.edit',$number) }}">Wijzigen</a>@endif
</section><a href="{{ route('breeding-numbers.index') }}">Terug naar kweeknummers</a>
@endsection
