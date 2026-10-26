<fieldset>
<legend>Persoonsgegevens</legend>
<div class="grid">
@foreach(['first_name'=>'Voornaam','last_name'=>'Achternaam','email'=>'E-mailadres','birth_date'=>'Geboortedatum'] as $field=>$label)<div class="field">
<label for="{{ $field }}">{{ $label }}</label>
<input id="{{ $field }}" name="{{ $field }}" type="{{ $field==='birth_date'?'date':($field==='email'?'email':'text') }}" value="{{ old($field,$field==='birth_date'?$member->birth_date?->format('Y-m-d'):$member->$field) }}" required maxlength="200">
</div>
@endforeach</div>
</fieldset>
<fieldset>
<legend>Adres</legend>
<div class="grid">
@foreach(['street'=>'Straat','house_number'=>'Huisnummer + toevoeging','postal_code'=>'Postcode','city'=>'Woonplaats'] as $field=>$label)<div class="field">
<label for="{{ $field }}">{{ $label }}</label>
<input id="{{ $field }}" name="{{ $field }}" value="{{ old($field,$member->address?->$field) }}" required maxlength="120">
</div>
@endforeach</div>
</fieldset>
<fieldset>
<legend>Lidmaatschap en NBvV</legend>
<div class="grid">
<div class="field">
<label for="member_type_id">Lidsoort</label>
<select id="member_type_id" name="member_type_id" required>
<option value="">Kies een lidsoort</option>
@foreach($types as $type)<option value="{{ $type->id }}" @selected(old('member_type_id',$member->member_type_id)==$type->id)>{{ $type->name }}</option>
@endforeach</select>
</div>
<div class="field">
<label for="nbvv_number">NBvV-lidnummer / kweeknummer</label>
<input id="nbvv_number" name="nbvv_number" maxlength="20" value="{{ old('nbvv_number',$member->nbvv_number) }}">
<small>Verplicht voor jeugd en volwassen leden; leeg voor gastleden. “Ringnummer” in de opdracht betekent hier hetzelfde nummer.</small>
</div>
@unless($public??false)<div class="field">
<label for="registered_at">Aanmelddatum</label>
<input id="registered_at" name="registered_at" type="date" value="{{ old('registered_at',$member->registered_at?->format('Y-m-d')??today()->format('Y-m-d')) }}" required>
</div>
@endunless</div>
</fieldset>


@unless($public??false)
<div class="field">
<input type="hidden" name="is_active" value="0">
<label>
<input type="checkbox" name="is_active" value="1" @checked(old('is_active',$member->exists?$member->is_active:true))> Lidmaatschap administratief ingeschakeld</label>
<small>De daadwerkelijke actieve status houdt daarnaast rekening met de ingangs- en einddatum. Uitschakelen is geen afmelding of terugbetaling.</small>
</div>

@endunless
