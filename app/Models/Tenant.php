<?php

namespace App\Models;

use App\AccountType;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Cashier\Billable;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use Billable;
    use HasDatabase;
    use HasDomains;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'status',
            'plan',
            'stripe_id',
            'pm_type',
            'pm_last_four',
            'trial_ends_at',
            'signup_city',
            'street_address',
            'city',
            'state',
            'country',
            'featured_image_url',
            'account_type',
        ];
    }

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'account_type' => AccountType::class,
        ];
    }

    public function availability(): HasOne
    {
        return $this->hasOne(TenantAvailability::class);
    }

    /**
     * How many logins the getting started guide keeps showing up for.
     */
    public const ONBOARDING_GUIDE_LOGINS = 5;

    public function showsOnboardingGuide(): bool
    {
        return ! $this->onboarding_hidden
            && (int) $this->onboarding_logins <= self::ONBOARDING_GUIDE_LOGINS;
    }

    public function restartOnboardingGuide(): void
    {
        $this->update(['onboarding_hidden' => false, 'onboarding_logins' => 0]);
    }

    public function hideOnboardingGuide(): void
    {
        $this->update(['onboarding_hidden' => true]);
    }

    public function isPersonal(): bool
    {
        return $this->account_type === AccountType::Personal;
    }

    /**
     * Límite configurado para un recurso según el plan (tablas `plans`/`plan_items`,
     * administradas desde Admin → Planes). Null significa ilimitado. Si el plan
     * o el elemento no existen, también se trata como ilimitado.
     */
    public static function planLimit(?string $plan, string $resource): ?int
    {
        return PlanItem::query()
            ->whereHas('plan', fn ($q) => $q->where('slug', $plan))
            ->where('name', $resource)
            ->value('quantity');
    }

    /**
     * Si un tenant en el plan dado todavía puede crear más de este recurso.
     */
    public static function withinPlanLimit(?string $plan, string $resource, int $currentCount): bool
    {
        $limit = self::planLimit($plan, $resource);

        return $limit === null || $currentCount < $limit;
    }

    /**
     * Si el plan dado tiene habilitada una función (existe una fila en `plan_items`
     * con ese nombre). A diferencia de `planLimit()`, "sin fila" acá significa
     * "función no disponible", no "ilimitado".
     */
    public static function hasFeature(?string $plan, string $resource): bool
    {
        return PlanItem::query()
            ->whereHas('plan', fn ($q) => $q->where('slug', $plan))
            ->where('name', $resource)
            ->exists();
    }
}
