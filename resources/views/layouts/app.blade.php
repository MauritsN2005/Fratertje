<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', "'t Fratertje")</title>
<link rel="stylesheet" href="{{ asset('app.css') }}">
<script src="{{ asset('app.js') }}" defer>
</script>
</head>
<body>
<header>
<a class="brand" href="{{ route('home') }}">◈ 't Fratertje <small>Vogelvereniging · ledenadministratie</small>
</a>
<nav aria-label="Hoofdnavigatie">
@auth<a href="{{ route('dashboard') }}">Dashboard</a>
<a href="{{ route('members.index') }}">Leden</a>
<a href="{{ route('member-types.index') }}">Lidsoorten</a>
<a href="{{ route('breeding-numbers.index') }}">Kweeknummers</a>
<a href="{{ route('requests.index') }}">Aanvragen</a>
<a href="{{ route('invoices.index') }}">Facturen</a>
<form method="post" action="{{ route('logout') }}">
@csrf<button class="quiet">Uitloggen</button>
</form>
@else<a href="{{ route('registration.create') }}">Lid worden</a>
<a href="{{ route('cancellation.create') }}">Afmelden</a>
<a href="{{ route('login') }}">Administratie</a>
@endauth</nav>
</header>
<main>@if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())<div class="errors" role="alert">
<strong>Controleer je invoer</strong>
<ul>
@foreach($errors->all() as $error)<li>{{ $error }}</li>
@endforeach</ul>
</div>
@endif
@yield('content')</main>
<footer>'t Fratertje · Zorgvuldig geregeld, samen voor onze vogels.</footer>
</body>
</html>
