<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Exceptions\NotASubdomainException;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

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

            return $request->getSchemeAndHttpHost().'/login';
        });

        // La sesión debe iniciar dentro del contexto del tenant (conexión de BD ya cambiada),
        // si no, la sesión autenticada se guarda en la BD central y el tenant nunca la ve.
        $middleware->prependToPriorityList(
            StartSession::class,
            InitializeTenancyBySubdomain::class,
        );
        $middleware->prependToPriorityList(
            InitializeTenancyBySubdomain::class,
            PreventAccessFromCentralDomains::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotASubdomainException $e, $request) {
            return redirect($request->getSchemeAndHttpHost().'/admin/login');
        });
    })->create();
