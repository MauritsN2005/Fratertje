@extends('layouts.app')

@section('content')<div class="card narrow">
<p class="eyebrow">Alleen voor de administratie</p>
<h1>Welkom terug</h1>
<form method="post" action="{{ route('login') }}">
@csrf<div class="field">
<label for="email">E-mailadres</label>
<input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
</div>
<div class="field">
<label for="password">Wachtwoord</label>
<input id="password" type="password" name="password" required autocomplete="current-password">
</div>
<button>Inloggen</button>
</form>
</div>
@endsection
