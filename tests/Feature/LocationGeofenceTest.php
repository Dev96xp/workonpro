<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    (new PlanSeeder)->run();

    Plan::where('slug', 'enterprise')->first()->items()->create(['name' => 'employees', 'quantity' => null]);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

function makeGeofenceTestTenant(string $id): Tenant
{
    $tenant = Tenant::create([
        'id' => $id,
        'name' => "Tenant {$id}",
        'email' => "{$id}@example.com",
        'status' => 'active',
        'plan' => 'enterprise',
    ]);
    $tenant->domains()->create(['domain' => $id]);

    return $tenant;
}

it('measures distance between two coordinates in feet', function () {
    $tenant = makeGeofenceTestTenant('geodistance');
    tenancy()->initialize($tenant);

    // Mexico City zócalo.
    $office = Location::factory()->create(['latitude' => 19.4326077, 'longitude' => -99.1332080, 'radius_feet' => 100]);

    // Same point: distance should be ~0.
    expect($office->distanceInFeetTo(19.4326077, -99.1332080))->toBeLessThan(1)
        ->and($office->isWithinRadius(19.4326077, -99.1332080))->toBeTrue();

    // Roughly 1.4km away: well outside a 100ft radius.
    expect($office->isWithinRadius(19.4450, -99.1332080))->toBeFalse();

    $tenant->delete();
});

it('lets an admin create a building with captured coordinates', function () {
    $tenant = makeGeofenceTestTenant('geocreate');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.locations')
        ->set('name', 'Almacén 1')
        ->set('latitude', '19.4326077')
        ->set('longitude', '-99.1332080')
        ->set('radius_feet', '150')
        ->call('save')
        ->assertHasNoErrors();

    $location = Location::where('name', 'Almacén 1')->first();

    expect($location)->not->toBeNull()
        ->and((float) $location->latitude)->toBe(19.4326077)
        ->and($location->radius_feet)->toBe(150);

    $tenant->delete();
});

it('blocks clock-in when the employee is outside the building radius', function () {
    $tenant = makeGeofenceTestTenant('geoblock');
    tenancy()->initialize($tenant);

    Location::factory()->create(['name' => 'Almacén 1', 'latitude' => 19.4326077, 'longitude' => -99.1332080, 'radius_feet' => 100]);
    $employee = Employee::factory()->create();
    $this->actingAs($employee, 'employee');

    Volt::test('tenant.clock-in')
        ->set('location', 'Almacén 1')
        ->call('toggle', 19.4450, -99.1332080)
        ->assertSet('locationError', fn ($value) => $value !== '');

    expect(Attendance::count())->toBe(0);

    $tenant->delete();
});

it('allows clock-in when the employee is inside the building radius', function () {
    $tenant = makeGeofenceTestTenant('geoallow');
    tenancy()->initialize($tenant);

    Location::factory()->create(['name' => 'Almacén 1', 'latitude' => 19.4326077, 'longitude' => -99.1332080, 'radius_feet' => 100]);
    $employee = Employee::factory()->create();
    $this->actingAs($employee, 'employee');

    Volt::test('tenant.clock-in')
        ->set('location', 'Almacén 1')
        ->call('toggle', 19.4326077, -99.1332080)
        ->assertSet('locationError', '');

    expect(Attendance::count())->toBe(1);

    $tenant->delete();
});

it('does not enforce geolocation for a building without coordinates', function () {
    $tenant = makeGeofenceTestTenant('geonone');
    tenancy()->initialize($tenant);

    Location::factory()->create(['name' => 'Almacén 2', 'latitude' => null, 'longitude' => null]);
    $employee = Employee::factory()->create();
    $this->actingAs($employee, 'employee');

    Volt::test('tenant.clock-in')
        ->set('location', 'Almacén 2')
        ->call('toggle')
        ->assertSet('locationError', '');

    expect(Attendance::count())->toBe(1);

    $tenant->delete();
});
