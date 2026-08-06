<?php

use App\Models\BusinessProfile;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Volt\Volt;

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('saves license and insurance info from the tenant settings form', function () {
    $tenant = Tenant::create([
        'id' => 'licensetenant',
        'name' => 'License Test',
        'email' => 'license@example.com',
        'status' => 'active',
        'plan' => 'pro',
    ]);
    $tenant->domains()->create(['domain' => 'licensetenant']);

    tenancy()->initialize($tenant);

    $this->actingAs(User::factory()->create());

    Volt::test('tenant.settings')
        ->set('business_name', 'License Test')
        ->set('has_license', true)
        ->set('license_number', 'LIC-123')
        ->set('has_insurance', true)
        ->set('insurance_number', 'POL-456')
        ->call('saveProfile');

    $profile = BusinessProfile::first();

    expect($profile->has_license)->toBeTrue()
        ->and($profile->license_number)->toBe('LIC-123')
        ->and($profile->has_insurance)->toBeTrue()
        ->and($profile->insurance_number)->toBe('POL-456');

    tenancy()->end();

    $tenant->delete();
});
