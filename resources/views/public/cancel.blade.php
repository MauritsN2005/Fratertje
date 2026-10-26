@extends('layouts.app')

@section('content')<div class="card narrow">
<h1>Lidmaatschap opzeggen</h1>
<p>Vul het e-mailadres in dat bij je lidmaatschap hoort. Je ontvangt een beveiligde link om je afmelding te bevestigen.</p>
<form method="post" action="{{ route('cancellation.send') }}">
@csrf<div class="field">
<label for="email">E-mailadres</label>
<input id="email" type="email" name="email" required>
</div>
<button>Bevestigingslink aanvragen</button>
</form>
</div>
@endsection
