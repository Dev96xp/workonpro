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
     * Límite configurado para un recurso según el plan. Null significa ilimitado.
     * Un admin puede sobrescribir el default de config/plans.php desde el panel
     * de Admin ("Planes"); esas ediciones se guardan en Setting.
     */
    public static function planLimit(?string $plan, string $resource): ?int
    {
        $override = Setting::get("plan_limit_{$plan}_{$resource}");

        if ($override !== null) {
            return $override === '' ? null : (int) $override;
        }

        return config("plans.limits.{$plan}.{$resource}");
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
