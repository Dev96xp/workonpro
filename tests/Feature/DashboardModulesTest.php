<?php

use App\Models\Client;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    (new PlanSeeder)->run();
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    Tenant::query()->where('id', 'modulestenant')->get()->each->delete();
});

function visitDashboardModules(string $plan): array
{
    $tenant = Tenant::create([
        'id' => 'modulestenant',
        'name' => 'Modules Co',
        'email' => 'modules@example.com',
        'status' => 'active',
        'plan' => $plan,
    ]);
    $tenant->domains()->create(['domain' => 'modulestenant']);
    tenancy()->initialize($tenant);
    test()->actingAs(User::factory()->create());

    return collect(Volt::test('tenant.dashboard')->viewData('modules'))
        ->map(fn (array $module) => parse_url($module['url'], PHP_URL_PATH) ?? '/')
        ->all();
}

it('links every module included in the basic plan', function () {
    $paths = visitDashboardModules('basic');

    expect($paths)->toContain('/clients', '/services', '/coupons', '/availability', '/images', '/settings', '/')
        ->not->toContain('/invoices', '/employees', '/appointments', '/card');
});

it('shows the totals on the module tiles instead of separate stat cards', function () {
    $paths = visitDashboardModules('basic');
    Client::create(['name' => 'Ana']);
    Client::create(['name' => 'Luis']);

    $modules = collect(Volt::test('tenant.dashboard')->viewData('modules'))
        ->keyBy(fn (array $module) => parse_url($module['url'], PHP_URL_PATH) ?? '/');

    expect($paths)->toContain('/clients')
        ->and($modules['/clients']['count'])->toBe(2)
        ->and($modules['/images']['count'])->toBe(0)
        ->and($modules['/services'])->not->toHaveKey('count');
});

it('adds the modules unlocked by the plan features', function () {
    $pro = Plan::where('slug', 'pro')->first();
    foreach (['invoices', 'employees', 'appointments', 'businesscard'] as $feature) {
        $pro->items()->create(['name' => $feature, 'quantity' => null]);
    }

    $paths = visitDashboardModules('pro');

    expect($paths)->toContain(
        '/appointments', '/invoices', '/products', '/product-categories', '/taxes',
        '/employees', '/locations', '/attendance', '/attendance/payroll', '/card',
    );
});
