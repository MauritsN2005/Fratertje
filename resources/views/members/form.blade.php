@extends('layouts.app')

@section('content')<h1>{{ $member->exists?'Lid wijzigen':'Lid toevoegen' }}</h1>
<p>De ingangsdatum wordt automatisch berekend: aanmelding + 21 dagen, gevolgd door de eerste dag van de volgende maand.</p>
<form class="card" method="post" action="{{ $member->exists?route('members.update',$member):route('members.store') }}">
@csrf @if($member->exists) 
@method('PUT') 
@endif 
@include('members.fields')<div class="actions">
<button>Gegevens opslaan</button>
<a class="button secondary" href="{{ route('members.index') }}">Annuleren</a>
</div>
</form>
@endsection
