<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'registered_at' => 'date', 'starts_at' => 'date', 'ends_at' => 'date', 'is_active' => 'boolean', 'consented_at' => 'datetime'];
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class)->withTrashed();
    }

    public function memberType(): BelongsTo
    {
        return $this->belongsTo(MemberType::class)->withTrashed();
    }

    public function breedingNumber(): HasOne
    {
        return $this->hasOne(BreedingNumber::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function getActiveNowAttribute(): bool
    {
        return ! $this->trashed() && $this->is_active && $this->starts_at->lte(today()) && (! $this->ends_at || $this->ends_at->gt(today()));
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereDate('starts_at', '<=', today())->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>', today()));
    }
}
