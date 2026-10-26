@extends('layouts.app')

@section('content')<div class="bar">
<h1>Kweeknummers / ringnummers</h1>
<a class="button" href="{{ route('breeding-numbers.create') }}">Nummer en lid toevoegen</a>
</div>
<p>Het kweeknummer is hetzelfde als het NBvV-lidnummer. Archiveren van een nummer archiveert ook het lid, zodat er nooit een actief NBvV-lid zonder nummer ontstaat.</p>
<div class="table-scroll">
<table>
<thead>
<tr>
<th>Nummer</th>
<th>Lid</th>
<th>Uitgiftejaar</th>
<th>Status</th>
<th>Beheer</th>
</tr>
</thead>
<tbody>
@forelse($numbers as $number)<tr>
<td>{{ $number->breeding_number }}</td>
<td>
<a href="{{ route('members.show',$number->member_id) }}">{{ $number->member->full_name }}</a>
</td>
<td>{{ $number->issue_year }}</td>
<td>{{ $number->trashed()?'Archief':($number->member->active_now?'Actief':'Gereserveerd / niet actief') }}</td>
<td>
@unless($number->trashed())<a href="{{ route('breeding-numbers.edit',$number) }}">Wijzigen</a>
<form class="inline" method="post" action="{{ route('breeding-numbers.destroy',$number) }}" data-confirm="Kweeknummer én bijbehorend lid archiveren?">
@csrf 
@method('DELETE')<button class="danger">Archiveren</button>
</form>
@endunless</td>
</tr>
@empty<tr>
<td colspan="5">Nog geen kweeknummers.</td>
</tr>
@endforelse</tbody>
</table>
</div>{{ $numbers->links() }}
@endsection
