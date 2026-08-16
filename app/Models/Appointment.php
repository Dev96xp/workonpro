<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_ATTENDED = 'attended';

    public const STATUS_NO_SHOW = 'no_show';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * Estatus seleccionables desde el formulario de edición (no incluye
     * "archived", que se maneja como una acción aparte, no un valor del selector).
     */
    public const EDITABLE_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_CANCELLED,
        self::STATUS_ATTENDED,
        self::STATUS_NO_SHOW,
    ];

    protected $fillable = [
        'client_id',
        'title',
        'starts_at',
        'ends_at',
        'notes',
        'status',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_ARCHIVED);
    }
}
