<?php

use App\Http\Controllers\PwaManifestController;
use App\Http\Middleware\SetLocale;
use App\Models\Plan;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

$centralDomain = parse_url(config('app.url'), PHP_URL_HOST);

Route::domain($centralDomain)->middleware(SetLocale::class)->group(function () {
    Volt::route('/', 'welcome-v2')->name('home');

    Route::get('/manifest.webmanifest', PwaManifestController::class)->name('pwa.manifest');

    Route::redirect('/welcome-v2', '/', 301)->name('welcome.v2');

    Route::get('/welcome-v1', function () {
        abort_unless(app()->environment('local', 'testing') || auth('super_admin')->check(), 404);

        return view('welcome', [
            'plans' => Plan::query()->whereIn('slug', ['basic', 'pro', 'enterprise'])->get()->keyBy('slug'),
        ]);
    })->name('welcome.v1');

    Route::get('/businesscard', function () {
        return view('businesscard');
    })->name('businesscard');

    Route::get('/businesscard/vcard', function () {
        $vcard = implode("\r\n", [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:Ramirez;Ricardo;;;',
            'FN:Ricardo Ramirez',
            'ORG:Workon',
            'TITLE:Fundador',
            'TEL;TYPE=CELL,VOICE:+17704122535',
            'URL:'.url('/'),
            'END:VCARD',
        ]);

        return response($vcard, 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="ricardo-ramirez-workon.vcf"',
        ]);
    })->name('businesscard.vcard');

    Route::get('/terminos', function () {
        return view('terms');
    })->name('terms');

    Route::get('/privacidad', function () {
        return view('privacy');
    })->name('privacy');

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
