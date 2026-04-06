<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessImage extends Model
{
    protected $fillable = [
        'filename',
        'original_name',
        'path',
        'mime_type',
        'size',
        'compressed_size',
        'is_featured',
        'imageable_type',
        'imageable_id',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    public function imageable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /** Scope for gallery images (not attached to any model) */
    public function scopeGallery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('imageable_type');
    }

    public static function setFeatured(int $id): void
    {
        self::query()->update(['is_featured' => false]);
        self::query()->where('id', $id)->update(['is_featured' => true]);
    }

    public function url(): string
    {
        return global_asset('storage/' . $this->path);
    }
}
