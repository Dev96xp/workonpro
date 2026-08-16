<?php

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    (new PlanSeeder)->run();

    foreach (['pro', 'enterprise'] as $slug) {
        Plan::where('slug', $slug)->first()->items()->create(['name' => 'appointments', 'quantity' => null]);
    }
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

function makeAppointmentTestTenant(string $id, string $plan): Tenant
{
    $tenant = Tenant::create([
        'id' => $id,
        'name' => "Tenant {$id}",
        'email' => "{$id}@example.com",
        'status' => 'active',
        'plan' => $plan,
    ]);
    $tenant->domains()->create(['domain' => $id]);

    return $tenant;
}

it('warns about an overlapping appointment but not a non-overlapping one', function () {
    $tenant = makeAppointmentTestTenant('apptoverlap', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $client = Client::create(['name' => 'Cliente Uno']);
    Appointment::create([
        'client_id' => $client->id,
        'title' => 'Cita A',
        'starts_at' => '2026-09-01 10:00:00',
        'ends_at' => '2026-09-01 11:00:00',
    ]);

    $overlapping = Volt::test('tenant.appointments')
        ->call('openCreate', '2026-09-01')
        ->set('date', '2026-09-01')
        ->set('start_hour', '10')
        ->set('start_minute', '30')
        ->set('end_hour', '11')
        ->set('end_minute', '30');

    expect($overlapping->instance()->overlapWarning())->not->toBeNull();

    $clear = Volt::test('tenant.appointments')
        ->call('openCreate', '2026-09-01')
        ->set('date', '2026-09-01')
        ->set('start_hour', '12')
        ->set('start_minute', '00')
        ->set('end_hour', '13')
        ->set('end_minute', '00');

    expect($clear->instance()->overlapWarning())->toBeNull();

    $tenant->delete();
});

it('creates an appointment through the Volt component despite the overlap warning', function () {
    $tenant = makeAppointmentTestTenant('apptcreate', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $client = Client::create(['name' => 'Cliente Dos']);

    Volt::test('tenant.appointments')
        ->call('openCreate', '2026-09-02')
        ->set('client_id', (string) $client->id)
        ->set('title', 'Visita técnica')
        ->set('date', '2026-09-02')
        ->set('start_hour', '09')
        ->set('start_minute', '00')
        ->set('end_hour', '10')
        ->set('end_minute', '00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $appointment = Appointment::first();

    expect($appointment)->not->toBeNull()
        ->and($appointment->title)->toBe('Visita técnica')
        ->and($appointment->starts_at->format('H:i'))->toBe('09:00')
        ->and($appointment->ends_at->format('H:i'))->toBe('10:00');

    $tenant->delete();
});

it('rejects an end time that is not after the start time', function () {
    $tenant = makeAppointmentTestTenant('apptbadtime', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $client = Client::create(['name' => 'Cliente Tres']);

    Volt::test('tenant.appointments')
        ->call('openCreate', '2026-09-03')
        ->set('client_id', (string) $client->id)
        ->set('title', 'Cita inválida')
        ->set('date', '2026-09-03')
        ->set('start_hour', '11')
        ->set('start_minute', '00')
        ->set('end_hour', '10')
        ->set('end_minute', '00')
        ->call('save')
        ->assertHasErrors('end_hour');

    expect(Appointment::count())->toBe(0);

    $tenant->delete();
});

it('archives an appointment instead of deleting it, and hides it from the calendar', function () {
    $tenant = makeAppointmentTestTenant('apptarchive', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $client = Client::create(['name' => 'Cliente Cuatro']);
    $appointment = Appointment::create([
        'client_id' => $client->id,
        'title' => 'Cita a archivar',
        'starts_at' => '2026-09-04 09:00:00',
        'ends_at' => '2026-09-04 10:00:00',
    ]);

    expect($appointment->status)->toBe(Appointment::STATUS_ACTIVE);

    Volt::test('tenant.appointments')
        ->call('confirmArchive', $appointment->id)
        ->call('archive')
        ->assertSet('showArchiveModal', false);

    $appointment->refresh();

    expect($appointment->status)->toBe(Appointment::STATUS_ARCHIVED)
        ->and(Appointment::count())->toBe(1)
        ->and(Appointment::visible()->count())->toBe(0);

    $tenant->delete();
});

it('blocks a basic tenant from accessing the appointments calendar', function () {
    $tenant = makeAppointmentTestTenant('apptbasic', 'basic');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.appointments')->assertStatus(403);

    $tenant->delete();
});

it('lets a pro tenant access the appointments calendar', function () {
    $tenant = makeAppointmentTestTenant('apptpro', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.appointments')->assertOk();

    $tenant->delete();
});
