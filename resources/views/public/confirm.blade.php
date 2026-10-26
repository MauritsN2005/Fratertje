@extends('layouts.app')

@section('content')<div class="card narrow">
<h1>Afmelding bevestigen</h1>
<p>{{ $member->full_name }}, je beoogde einddatum is {{ \App\Support\MembershipDates::effective(today()->toDateString())->format('d-m-Y') }}. De administratie verwerkt je verzoek en berekent een creditnota voor eventuele resterende, al gefactureerde maanden.</p>
<form method="post" action="{{ request()->fullUrl() }}">
@csrf<label class="consent">
<input type="checkbox" name="consent" value="1" required> Ik wil mijn lidmaatschap beëindigen volgens de genoemde termijn.</label>
<p>
<button>Afmelding indienen</button>
</p>
</form>
</div>
@endsection
