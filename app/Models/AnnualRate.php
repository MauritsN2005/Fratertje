<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualRate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['announced_at' => 'datetime', 'amount_cents' => 'integer'];
    }

    public function memberType(): BelongsTo
    {
        return $this->belongsTo(MemberType::class)->withTrashed();
    }
}
