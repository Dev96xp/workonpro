<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

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
        'phone',
        'whatsapp',
        'photo_path',
        'card_slug',
        'card_enabled',
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
            'card_enabled' => 'boolean',
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
     * Gives the employee a readable, unique link for their digital card ("juan-perez").
     */
    public function ensureCardSlug(): string
    {
        if ($this->card_slug) {
            return $this->card_slug;
        }

        $base = Str::slug($this->name) ?: 'employee';
        $slug = $base;
        $suffix = 2;

        while (self::query()->where('card_slug', $slug)->whereKeyNot($this->getKey())->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        $this->forceFill(['card_slug' => $slug])->saveQuietly();

        return $slug;
    }

    /**
     * A card is public when it is on, the person is active and it fits in the plan's limit.
     * If the tenant moves to a smaller plan, the oldest cards keep working.
     */
    public function hasPublicCard(): bool
    {
        if (! $this->card_enabled || $this->card_slug === null || ! $this->isActive()) {
            return false;
        }

        $limit = Tenant::teamCardLimit(tenant('plan'));

        return $limit === null
            || self::query()->cardCandidates()->orderBy('id')->limit($limit)->pluck('id')->contains($this->id);
    }

    /**
     * How many more cards the plan allows. Null means unlimited.
     */
    public static function cardSlotsLeft(): ?int
    {
        $limit = Tenant::teamCardLimit(tenant('plan'));

        return $limit === null ? null : max(0, $limit - self::query()->cardCandidates()->count());
    }

    /**
     * @param  Builder<Employee>  $query
     */
    public function scopeCardCandidates(Builder $query): void
    {
        $query->where('card_enabled', true)
            ->whereNotNull('card_slug')
            ->where('status', self::STATUS_ACTIVE);
    }

    public function cardUrl(): ?string
    {
        return $this->card_slug ? url('/card/'.$this->card_slug) : null;
    }

    /**
     * Replaces the card photo with a compressed 600px square WebP, or removes it when null.
     * Files live next to the other tenant images (outside the tenant-suffixed disk).
     */
    public function replacePhoto(?UploadedFile $file): void
    {
        if ($this->photo_path && file_exists($oldPath = base_path("storage/app/public/{$this->photo_path}"))) {
            unlink($oldPath);
        }

        $path = null;

        if ($file) {
            $tenantId = tenant('id');
            $filename = Str::random(16).'.webp';
            $directory = base_path("storage/app/public/tenants/{$tenantId}/employees");

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            ImageManager::withDriver(new Driver)
                ->read($file->getRealPath())
                ->cover(600, 600)
                ->toWebp(85)
                ->save("{$directory}/{$filename}");

            $path = "tenants/{$tenantId}/employees/{$filename}";
        }

        $this->update(['photo_path' => $path]);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? global_asset('storage/'.$this->photo_path) : null;
    }

    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->filter()->take(2)
            ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('') ?: '?';
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
