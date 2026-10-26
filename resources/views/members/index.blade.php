@extends('layouts.app')

@section('content')<div class="bar">
<div>
<p class="eyebrow">Ledenadministratie</p>
<h1>{{ request('archive')?'Ledenarchief':'Onze leden' }}</h1>
</div>
<div class="actions">
<a class="button secondary" href="{{ route('members.index',['archive'=>request('archive')?0:1]) }}">{{ request('archive')?'Terug naar leden':'Archief bekijken' }}</a>
<a class="button" href="{{ route('members.create') }}">+ Lid toevoegen</a>
</div>
</div>
<form class="card filters" method="get">
<input type="hidden" name="archive" value="{{ request('archive',0) }}">
<div class="field">
<label for="q">Zoeken</label>
<input id="q" name="q" value="{{ request('q') }}" placeholder="Naam, lidsoort of kweeknummer">
</div>
<div class="field">
<label for="type">Lidsoort</label>
<select id="type" name="type">
<option value="">Alle lidsoorten</option>
@foreach($types as $type)<option value="{{ $type->id }}" @selected(request('type')==$type->id)>{{ $type->name }}</option>
@endforeach</select>
</div>
<div class="field">
<label for="status">Status</label>
<select id="status" name="status">
<option value="">Alle statussen</option>
<option value="active" @selected(request('status')==='active')>Actief</option>
<option value="inactive" @selected(request('status')==='inactive')>Niet actief</option>
</select>
</div>
<button>Zoeken</button>
</form>
<div class="table-scroll">
<table>
<thead>
<tr>
<th>Naam</th>
<th>Lidsoort</th>
<th>Woonplaats</th>
<th>Kweeknummer</th>
<th>Status</th>
</tr>
</thead>
<tbody>
@forelse($members as $member)<tr>
<td>
<a href="{{ route('members.show',$member->id) }}">{{ $member->full_name }}</a>
</td>
<td>{{ $member->memberType->name }}</td>
<td>{{ $member->address->city }}</td>
<td>{{ $member->nbvv_number??'—' }}</td>
<td>
<span class="badge">{{ $member->trashed()?'Gearchiveerd':($member->active_now?'Actief':'Niet actief') }}</span>
</td>
</tr>
@empty<tr>
<td colspan="5">Geen leden gevonden.</td>
</tr>
@endforelse</tbody>
</table>
</div>{{ $members->links() }}
@endsection
