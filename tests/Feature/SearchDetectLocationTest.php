<?php

use App\Models\BusinessProfile;
use App\Models\Service;
use App\Models\Tenant;
use Livewire\Volt\Volt;

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

function createSearchTestListing(string $id, string $city): Tenant
{
    $tenant = Tenant::create([
        'id' => $id,
        'name' => "Business {$id}",
        'email' => "{$id}@example.com",
        'status' => 'active',
        'plan' => 'pro',
    ]);
    $tenant->domains()->create(['domain' => $id]);

    tenancy()->initialize($tenant);
    BusinessProfile::create(['business_name' => "Business {$id}", 'city' => $city]);
    Service::create([
        'name' => "Service {$id}",
        'category' => 'other',
        'price' => 100,
        'is_active' => true,
    ]);
    tenancy()->end();

    return $tenant;
}

it('selects the matching city case-insensitively when a location is detected', function () {
    createSearchTestListing('detectcity', 'Monterrey');

    Volt::test('search.index')
        ->call('detectCity', 'monterrey')
        ->assertSet('city', 'Monterrey')
        ->assertSet('locationNotFound', false);
});

it('flags the location as not found when no listing matches the detected city', function () {
    createSearchTestListing('detectcitymiss', 'Monterrey');

    Volt::test('search.index')
        ->call('detectCity', 'Guadalajara')
        ->assertSet('city', '')
        ->assertSet('locationNotFound', true);
});
