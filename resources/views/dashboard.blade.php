@extends('layouts.app')

@section('content')<p class="eyebrow">Overzicht · {{ now()->format('d-m-Y') }}</p>
<div class="bar">
<h1>De vereniging in beeld</h1>
<a class="button" href="{{ route('members.create') }}">+ Lid toevoegen</a>
</div>
<div class="grid">
<div class="card">
<div class="stat">{{ $active }}</div>Actieve leden</div>
<a class="card" href="{{ route('requests.index') }}">
<div class="stat">{{ $pending }}</div>Aanvragen om te verwerken</a>
<a class="card" href="{{ route('members.index',['archive'=>1]) }}">
<div class="stat">{{ $archived }}</div>Leden in het archief</a>
</div>
<div class="card">
<h2>Leden per lidsoort</h2>
@foreach($types as $type)<p>{{ $type->name }} <strong>{{ $type->members_count }}</strong>
</p>
@endforeach</div>
<p class="muted">Nieuwe aan- en afmeldingen verschijnen direct bij Aanvragen. Controleer deze wachtrij dagelijks.</p>
@endsection
