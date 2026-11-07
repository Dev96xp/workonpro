<?php

use App\Models\ServiceListing;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Support\Facades\Hash;

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    Tenant::query()->whereIn('id', array_keys(DemoTenantSeeder::TENANTS))->get()->each->delete();
});

it('creates five business and five personal demo tenants that can log in', function () {
    (new DemoTenantSeeder)->run();
    (new DemoTenantSeeder)->run();

    $tenants = Tenant::query()->whereIn('id', array_keys(DemoTenantSeeder::TENANTS))->get();

    expect($tenants)->toHaveCount(10)
        ->and($tenants->filter->isPersonal())->toHaveCount(5)
        ->and($tenants->pluck('city')->unique()->diff(['Omaha', 'Council Bluffs']))->toBeEmpty()
        ->and($tenants->firstWhere('id', 'concrete')->email)->toBe('admin@concrete.com')
        ->and($tenants->firstWhere('id', 'jorgelopez')->email)->toBe('jorgel@google.com');

    $concrete = $tenants->firstWhere('id', 'concrete');
    $concrete->run(function () {
        expect(Hash::check(DemoTenantSeeder::PASSWORD, User::firstWhere('email', 'admin@concrete.com')->password))->toBeTrue();
    });

    expect(ServiceListing::where('tenant_id', 'concrete')->pluck('category')->unique()->all())->toBe(['construction']);
});
