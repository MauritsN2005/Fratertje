@extends('layouts.app')

@section('content')<div class="bar">
<h1>Lidsoorten en tarieven</h1>
<a class="button" href="{{ route('member-types.create') }}">Lidsoort toevoegen</a>
</div>
<p>Er zijn precies drie categorieën. Tariefwijzigingen gelden vanaf volgend jaar en worden meteen op de openbare pagina aangekondigd.</p>
<div class="grid">
@foreach($types as $type)<section class="card">
<p class="eyebrow">{{ $type->code }} {{ $type->trashed()?'· archief':'' }}</p>
<h2>{{ $type->name }}</h2>
<p>{{ $type->description }}</p>
@foreach($type->rates->sortBy('year') as $rate)<p>Vanaf {{ $rate->year }}: <strong>{{ \App\Support\MembershipDates::money($rate->amount_cents) }}</strong>/jaar</p>
@endforeach<div class="actions">@if($type->trashed())<form method="post" action="{{ route('member-types.restore',$type->id) }}">
@csrf<button>Herstellen</button>
</form>
@else<a href="{{ route('member-types.edit',$type) }}" class="button">Wijzigen</a>
<form method="post" action="{{ route('member-types.destroy',$type) }}" data-confirm="Deze lidsoort archiveren? Dit kan alleen als er geen gekoppelde leden of aanvragen zijn.">
@csrf 
@method('DELETE')<button class="danger">Archiveren</button>
</form>
@endif</div>
</section>
@endforeach</div>
@endsection
