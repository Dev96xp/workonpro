<?php

use App\Http\Controllers\Auth\AdminLoginController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::prefix('admin')->name('admin.')->group(function () {

    // Guest routes
    Route::middleware('guest:super_admin')->group(function () {
        Volt::route('login', 'admin.login')->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');
    });

    // Authenticated routes
    Route::middleware('auth:super_admin')->group(function () {
        Volt::route('dashboard', 'admin.dashboard')->name('dashboard');

        Route::prefix('tenants')->name('tenants.')->group(function () {
            Volt::route('/', 'admin.tenants.index')->name('index');
            Volt::route('/create', 'admin.tenants.create')->name('create');
            Volt::route('/{tenant}', 'admin.tenants.show')->name('show');
            Volt::route('/{tenant}/edit', 'admin.tenants.edit')->name('edit');
        });
    });

    Route::post('logout', function () {
        auth('super_admin')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('logout');
});
