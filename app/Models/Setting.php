<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Los settings viven solo en la base de datos central. Forzamos la
     * conexión explícitamente porque este modelo puede consultarse desde
     * dentro de un contexto de tenant (ej. límites de plan en el panel).
     */
    public function getConnectionName(): string
    {
        return config('tenancy.database.central_connection');
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
