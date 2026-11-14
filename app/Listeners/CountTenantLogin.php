<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class CountTenantLogin
{
    /**
     * Count each owner login inside a tenant panel, so the getting started
     * guide knows when to stop showing itself.
     */
    public function handle(Login $event): void
    {
        if ($event->guard !== 'web' || ! tenancy()->initialized) {
            return;
        }

        $tenant = tenant();

        $tenant->update(['onboarding_logins' => (int) $tenant->onboarding_logins + 1]);
    }
}
