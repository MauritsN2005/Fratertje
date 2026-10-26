@extends('layouts.app')

@section('content')<h1>Lidsoort {{ $type->exists?'wijzigen':'toevoegen' }}</h1>
<form class="card" method="post" action="{{ $type->exists?route('member-types.update',$type):route('member-types.store') }}">
@csrf @if($type->exists) 
@method('PUT') 
@else<div class="field">
<label for="code">Categorie (elke categorie kan één keer bestaan)</label>
<select id="code" name="code">
<option value="adult">Volwassen lid</option>
<option value="youth">Jeugdlid</option>
<option value="guest">Gastlid</option>
</select>
</div>
@endif<div class="field">
<label for="name">Naam</label>
<input id="name" name="name" value="{{ old('name',$type->name) }}" required>
</div>
<div class="field">
<label for="description">Omschrijving</label>
<textarea id="description" name="description" required>{{ old('description',$type->description) }}</textarea>
</div>
<div class="field">
<label for="price">Jaarprijs in euro's — geldig vanaf {{ now()->year+($type->exists?1:0) }}</label>
<input id="price" type="number" min="0" max="9999" step="0.01" name="price" value="{{ old('price',$type->exists?$type->rateFor(now()->year+1)/100:18) }}" required>
</div>
<button>Opslaan en tarief aankondigen</button>
</form>
@endsection
