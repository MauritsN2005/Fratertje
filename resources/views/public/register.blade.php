@extends('layouts.app')

@section('content')<h1>Word lid van 't Fratertje</h1>
<p>Je aanmelding wordt eerst gecontroleerd door de administratie. Je gegevens zijn niet openbaar.</p>
<form class="card" method="post" action="{{ route('registration.store') }}">
@csrf 
@include('members.fields',['public'=>true])<div class="field">
<label for="signer_name">Naam ondertekenaar (bij minderjarigen: ouder/verzorger)</label>
<input id="signer_name" name="signer_name" value="{{ old('signer_name') }}" maxlength="180" required>
</div>
<label class="consent">
<input type="checkbox" name="consent" value="1" required @checked(old('consent'))> Ik meld mij aan als lid, verklaar dat mijn gegevens juist zijn en ga akkoord met de getoonde contributie en ingangsdatumregels. Ik begrijp dat de vereniging mijn gegevens gebruikt voor de ledenadministratie. Bij een minderjarige dient de ouder/verzorger dit formulier in. Verklaring versie 2026-09-v1.</label>
<p>
<button>Aanmelding versturen</button>
</p>
</form>
@endsection
