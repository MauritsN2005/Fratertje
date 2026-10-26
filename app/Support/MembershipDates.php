<?php

namespace App\Support;

use Carbon\CarbonImmutable;

final class MembershipDates
{
    public static function effective(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date)->startOfDay()->addDays(21)->startOfMonth()->addMonth();
    }

    public static function youthRate(string $birthDate, int $year): bool
    {
        return CarbonImmutable::parse($birthDate)->year + 18 >= $year;
    }

    public static function money(int $cents): string
    {
        return '€ '.number_format($cents / 100, 2, ',', '.');
    }
}
