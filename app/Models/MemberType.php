<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class MemberType extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(AnnualRate::class);
    }

    public function rateFor(int $year): int
    {
        $rate = $this->rates()->where('year', '<=', $year)->orderByDesc('year')->first();
        if (! $rate) {
            throw ValidationException::withMessages(['year' => 'Er is geen tarief voor dit jaar.']);
        }

        return $rate->amount_cents;
    }
}
