<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Employee $employee) {
            $employee->forceFill(['employee_code' => $employee->generateEmployeeCode()])->saveQuietly();
        });
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Código único tipo "NOMB-YYMMDD-0007-4821": primeras 4 letras del nombre,
     * fecha de alta, el propio id (consecutivo real) y 4 dígitos random.
     */
    private function generateEmployeeCode(): string
    {
        $namePart = strtoupper(str_pad(substr(preg_replace('/[^A-Za-z]/', '', $this->name), 0, 4), 4, 'X'));
        $datePart = ($this->created_at ?? now())->format('ymd');
        $seqPart = str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
        $randomPart = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return "{$namePart}-{$datePart}-{$seqPart}-{$randomPart}";
    }
}
