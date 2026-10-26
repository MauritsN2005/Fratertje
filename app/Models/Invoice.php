<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'annual_cents' => 'integer', 'amount_cents' => 'integer'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function getNumberAttribute(): string
    {
        return $this->year.'-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
