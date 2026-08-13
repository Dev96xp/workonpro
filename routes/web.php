<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

$centralDomain = parse_url(config('app.url'), PHP_URL_HOST);

Route::domain($centralDomain)->middleware(SetLocale::class)->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::get('/lang/{locale}', function (string $locale) {
        if (in_array($locale, ['es', 'en'], true)) {
            $domain = parse_url(config('app.url'), PHP_URL_HOST);
            Cookie::queue(Cookie::forever('locale', $locale, path: '/', domain: '.'.$domain));
        }

        return redirect()->back();
    })->name('lang.switch');

    Route::view('dashboard', 'dashboard')
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

    Volt::route('/buscar', 'search.index')->name('search');

    Route::middleware(['auth'])->group(function () {
        Route::redirect('settings', 'settings/profile');

        Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
        Volt::route('settings/password', 'settings.password')->name('settings.password');
        Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    });

    // Business registration flow
    Route::prefix('signup')->name('register.')->group(function () {
        Volt::route('/', 'register.plans')->name('plans');
        Volt::route('/start', 'register.create')->name('create');
        Volt::route('/success', 'register.success')->name('success');
    });

    require __DIR__.'/auth.php';
});
