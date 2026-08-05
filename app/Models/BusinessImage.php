<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
        'is_logo',
        'imageable_type',
        'imageable_id',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_logo' => 'boolean',
        ];
    }

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Scope for gallery images (not attached to any model) */
    public function scopeGallery(Builder $query): Builder
    {
        return $query->whereNull('imageable_type');
    }

    public static function setFeatured(int $id): void
    {
        self::query()->update(['is_featured' => false]);
        self::query()->where('id', $id)->update(['is_featured' => true]);
    }

    public static function setLogo(int $id): void
    {
        self::query()->update(['is_logo' => false]);
        self::query()->where('id', $id)->update(['is_logo' => true]);
    }

    public function url(): string
    {
        return global_asset('storage/'.$this->path);
    }
}
