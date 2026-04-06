<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Livewire::setUpdateRoute(function ($handle, $path) {
            $centralDomains = config('tenancy.central_domains', []);
            $host = request()->getHost();
            $isTenant = ! in_array($host, $centralDomains);

            $middleware = ['web'];
            if ($isTenant) {
                $middleware[] = InitializeTenancyBySubdomain::class;
            }

            return Route::post($path, $handle)->middleware($middleware);
        });

    }
}
