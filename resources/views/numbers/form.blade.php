@extends('layouts.app')

@section('content')<h1>Kweeknummer wijzigen</h1>
<p>{{ $number->member->full_name }}</p>
<form class="card" method="post" action="{{ route('breeding-numbers.update',$number) }}">
@csrf 
@method('PUT')<div class="field">
<label for="breeding_number">Kweeknummer</label>
<input id="breeding_number" name="breeding_number" value="{{ old('breeding_number',$number->breeding_number) }}" maxlength="20" required>
</div>
<div class="field">
<label for="issue_year">Uitgiftejaar</label>
<input id="issue_year" name="issue_year" type="number" min="1900" max="{{ now()->year }}" value="{{ old('issue_year',$number->issue_year) }}" required>
</div>
<button>Opslaan</button>
</form>
@endsection
