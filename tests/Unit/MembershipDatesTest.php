<?php

use App\Support\MembershipDates;

it('calculates the first month strictly after the waiting period', function ($input, $expected) {
    expect(MembershipDates::effective($input)->toDateString())->toBe($expected);
})->with(['first example' => ['2026-03-05', '2026-04-01'], 'corrected second example' => ['2026-03-25', '2026-05-01'], 'year rollover' => ['2026-12-20', '2027-02-01'], 'exact first day' => ['2026-01-11', '2026-03-01'], 'leap year' => ['2028-02-08', '2028-03-01']]);
it('keeps the youth rate throughout the eighteenth birthday year', function ($birth, $year, $expected) {
    expect(MembershipDates::youthRate($birth, $year))->toBe($expected);
})->with([['2008-01-01', 2026, true], ['2008-12-31', 2026, true], ['2007-12-31', 2026, false]]);
