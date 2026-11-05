<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\TenantAvailabilityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Weekly "available to work" schedule a tenant publishes. Lives in the central database
 * so the central pages can show who is available right now without entering each tenant DB.
 *
 * @property array<int, array{day: int, start: string, end: string}>|null $slots Day follows Carbon (0 = Sunday … 6 = Saturday), times are "HH:MM" in $timezone.
 */
class TenantAvailability extends Model
{
    /** @use HasFactory<TenantAvailabilityFactory> */
    use CentralConnection, HasFactory;

    protected $fillable = [
        'tenant_id',
        'is_active',
        'timezone',
        'slots',
        'note',
        'hourly_rate',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'slots' => 'array',
            'hourly_rate' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @param  Builder<TenantAvailability>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isAvailableAt(CarbonInterface $moment): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $local = $moment->copy()->setTimezone($this->timezone);
        $time = $local->format('H:i');

        return $this->sortedSlots()->contains(fn (array $slot) => $slot['day'] === $local->dayOfWeek
            && $time >= $slot['start']
            && $time < $slot['end']);
    }

    /**
     * The slot for the moment's weekday in the tenant's own time zone, if one was set.
     *
     * @return array{day: int, start: string, end: string}|null
     */
    public function slotOn(CarbonInterface $moment): ?array
    {
        $local = $moment->copy()->setTimezone($this->timezone);

        return $this->sortedSlots()->firstWhere('day', $local->dayOfWeek);
    }

    /**
     * Tenant ids whose schedule covers the given moment.
     *
     * @return Collection<int, string>
     */
    public static function tenantIdsAvailableAt(CarbonInterface $moment): Collection
    {
        return static::query()
            ->active()
            ->get()
            ->filter(fn (TenantAvailability $availability) => $availability->isAvailableAt($moment))
            ->pluck('tenant_id')
            ->values();
    }

    /**
     * Slots ordered Monday → Sunday, the way people read a week.
     *
     * @return Collection<int, array{day: int, start: string, end: string}>
     */
    public function sortedSlots(): Collection
    {
        return collect($this->slots ?? [])
            ->map(fn (array $slot) => ['day' => (int) $slot['day'], 'start' => $slot['start'], 'end' => $slot['end']])
            ->sortBy(fn (array $slot) => ($slot['day'] + 6) % 7)
            ->values();
    }
}
