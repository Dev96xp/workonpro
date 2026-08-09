<?php

namespace App\Models;

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
            'featured_image_url',
        ];
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
}
