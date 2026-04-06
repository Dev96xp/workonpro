<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_amount',
        'max_uses',
        'uses_count',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value'      => 'decimal:2',
            'min_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'is_active'  => 'boolean',
        ];
    }

    public function images(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(BusinessImage::class, 'imageable');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasReachedMaxUses(): bool
    {
        return $this->max_uses && $this->uses_count >= $this->max_uses;
    }

    public function isValid(): bool
    {
        return $this->is_active && ! $this->isExpired() && ! $this->hasReachedMaxUses();
    }
}
