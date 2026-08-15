<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\ImageController;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenancyServiceProvider.
|
*/

use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    PreventAccessFromCentralDomains::class,
    InitializeTenancyBySubdomain::class,
    'web',
    SetLocale::class,
])->group(function () {
    // Public welcome page
    Volt::route('/', 'tenant.welcome')->name('tenant.welcome');

    Route::get('/lang/{locale}', function (Request $request) {
        $locale = $request->route('locale');

        if (in_array($locale, ['es', 'en'], true)) {
            $domain = parse_url(config('app.url'), PHP_URL_HOST);
            Cookie::queue(Cookie::forever('locale', $locale, path: '/', domain: '.'.$domain));
        }

        return redirect()->back();
    })->name('tenant.lang.switch');

    // Login
    Volt::route('/login', 'tenant.login')->name('tenant.login');
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    })->name('tenant.logout');

    // Panel protegido
    Route::middleware('auth')->group(function () {
        Volt::route('/dashboard', 'tenant.dashboard')->name('tenant.dashboard');
        Volt::route('/clients', 'tenant.clients')->name('tenant.clients');
        Volt::route('/services', 'tenant.services')->name('tenant.services');
        Volt::route('/coupons', 'tenant.coupons')->name('tenant.coupons');
        Volt::route('/images', 'tenant.images')->name('tenant.images');
        Route::post('/images/upload', [ImageController::class, 'store'])->name('tenant.images.upload');
        Volt::route('/product-categories', 'tenant.product-categories')->name('tenant.product-categories');
        Volt::route('/products', 'tenant.products')->name('tenant.products');
        Volt::route('/invoices', 'tenant.invoices')->name('tenant.invoices');
        Volt::route('/settings', 'tenant.settings')->name('tenant.settings');
    });
});
