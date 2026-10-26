<?php

use App\Models\Member;
use App\Models\MemberType;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('members:age', function () {
    $adult = MemberType::where('code', 'adult')->firstOrFail();
    $count = Member::where('membership_code', 'youth')->whereDate('birth_date', '<=', today()->subYears(18))->update(['member_type_id' => $adult->id, 'membership_code' => 'adult']);
    $this->info("{$count} jeugdleden omgezet. Jeugdtarief blijft gelden in het jaar van de 18e verjaardag.");
})->purpose('Zet jeugdleden vanaf hun 18e verjaardag om naar volwassen lid.');
Schedule::command('members:age')->dailyAt('00:05');
