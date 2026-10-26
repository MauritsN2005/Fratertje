@extends('layouts.app')

@section('content')<h1>Aanvragen en quarantaine</h1>
<p>Nieuwe aanmeldingen worden pas leden na goedkeuring. Een afmelding legt een einddatum vast en maakt waar nodig een creditnota.</p>
@forelse($requests as $item)<article class="card">
<div class="bar">
<h2>#{{ $item->id }} · {{ $item->kind==='registration'?'Aanmelding':'Afmelding' }}</h2>
<span class="badge">{{ ['pending'=>'Te verwerken','approved'=>'Goedgekeurd','rejected'=>'Afgewezen'][$item->status] }}</span>
</div>
<p>Ontvangen {{ $item->created_at->format('d-m-Y H:i') }} · Beoogde ingangs-/einddatum {{ $item->effective_at->format('d-m-Y') }}<br>Akkoord vastgelegd op {{ $item->consented_at->format('d-m-Y H:i') }} ({{ $item->consent_version }})</p>
<dl>
@foreach($item->payload as $key=>$value)<dt>{{ ['first_name'=>'Voornaam','last_name'=>'Achternaam','email'=>'E-mail','birth_date'=>'Geboortedatum','member_type_id'=>'Lidsoort-ID','nbvv_number'=>'Kweeknummer','street'=>'Straat','house_number'=>'Huisnummer','postal_code'=>'Postcode','city'=>'Woonplaats','registered_at'=>'Aanmelddatum','name'=>'Naam','signer_name'=>'Ondertekenaar'][$key]??$key }}</dt>
<dd>{{ $value }}</dd>
@endforeach</dl>@if($item->status==='pending')<form method="post" action="{{ route('requests.approve',$item) }}" data-confirm="Deze aanvraag goedkeuren en verwerken?">
@csrf<button>Goedkeuren en verwerken</button>
</form>
<form method="post" action="{{ route('requests.reject',$item) }}" class="filters">
<div class="field">
<label for="note-{{ $item->id }}">Reden bij afwijzen</label>
<input id="note-{{ $item->id }}" name="note" maxlength="1000" required>
</div>
@csrf<button class="danger">Afwijzen</button>
</form>
@elseif($item->note)<p>Reden: {{ $item->note }}</p>
@endif</article>
@empty<p>Er zijn nog geen aanvragen.</p>
@endforelse{{ $requests->links() }}
@endsection
