<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('admin/*')) {
                return route('admin.login');
            }

            return $request->getSchemeAndHttpHost() . '/login';
        });

        $middleware->prependToPriorityList(
            \Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain::class,
            \Illuminate\Session\Middleware\StartSession::class,
        );
        $middleware->prependToPriorityList(
            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
            \Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Stancl\Tenancy\Exceptions\NotASubdomainException $e, $request) {
            return redirect($request->getSchemeAndHttpHost() . '/admin/login');
        });
    })->create();
