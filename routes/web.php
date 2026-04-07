<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

$centralDomain = parse_url(config('app.url'), PHP_URL_HOST);

Route::domain($centralDomain)->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::view('dashboard', 'dashboard')
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

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
