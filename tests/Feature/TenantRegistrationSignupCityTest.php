<?php

use App\Models\Tenant;
use App\Services\IpGeolocationService;
use Livewire\Volt\Volt;

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('stores the geolocated signup city when a free tenant registers', function () {
    $this->mock(IpGeolocationService::class, function ($mock) {
        $mock->shouldReceive('city')->once()->andReturn('Miami');
    });

    request()->merge(['plan' => 'basic']);

    Volt::test('register.create')
        ->set('business_name', 'Signup City Test')
        ->set('subdomain', 'signupcitytest')
        ->set('email', 'signupcity@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('checkout');

    $tenant = Tenant::find('signupcitytest');

    expect($tenant)->not->toBeNull()
        ->and($tenant->signup_city)->toBe('Miami');

    $tenant->delete();
});
