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

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_VACATION = 'vacation';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_VACATION,
        self::STATUS_SUSPENDED,
    ];

    public const PERIOD_HOURLY = 'hourly';

    public const PERIOD_WEEKLY = 'weekly';

    public const PERIOD_BIWEEKLY = 'biweekly';

    public const PERIOD_MONTHLY = 'monthly';

    public const SALARY_PERIODS = [
        self::PERIOD_HOURLY,
        self::PERIOD_WEEKLY,
        self::PERIOD_BIWEEKLY,
        self::PERIOD_MONTHLY,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'salary',
        'salary_period',
        'status',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'salary' => 'decimal:2',
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

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
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
