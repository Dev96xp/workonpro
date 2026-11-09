<?php

use App\Models\Tenant;
use App\Models\TenantAvailability;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Volt\Volt;

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    Tenant::query()->where('id', 'availabilitytenant')->get()->each->delete();
});

function createAvailabilityTenant(): Tenant
{
    $tenant = Tenant::create([
        'id' => 'availabilitytenant',
        'name' => 'Availability Co',
        'email' => 'availability@example.com',
        'status' => 'active',
        'plan' => 'basic',
    ]);
    $tenant->domains()->create(['domain' => 'availabilitytenant']);

    return $tenant;
}

function createStoryTenant(string $id): Tenant
{
    return Tenant::withoutEvents(fn () => Tenant::create([
        'id' => $id,
        'name' => ucfirst($id),
        'email' => "{$id}@example.com",
        'status' => 'active',
        'plan' => 'basic',
        'featured_image_url' => "https://example.com/{$id}.webp",
    ]));
}

it('is available only inside a slot in its own time zone', function () {
    $availability = new TenantAvailability([
        'is_active' => true,
        'timezone' => 'America/New_York',
        'slots' => [['day' => 1, 'start' => '18:00', 'end' => '22:00']],
    ]);

    expect($availability->isAvailableAt(Carbon::parse('2026-11-02 23:30', 'UTC')))->toBeTrue()
        ->and($availability->isAvailableAt(Carbon::parse('2026-11-02 22:30', 'UTC')))->toBeFalse()
        ->and($availability->isAvailableAt(Carbon::parse('2026-11-03 03:00', 'UTC')))->toBeFalse()
        ->and($availability->isAvailableAt(Carbon::parse('2026-11-03 23:30', 'UTC')))->toBeFalse();
});

it('is never available while paused', function () {
    $availability = new TenantAvailability([
        'is_active' => false,
        'timezone' => 'UTC',
        'slots' => [['day' => 1, 'start' => '00:00', 'end' => '23:59']],
    ]);

    expect($availability->isAvailableAt(Carbon::parse('2026-11-02 12:00', 'UTC')))->toBeFalse();
});

it('saves the weekly schedule from the tenant dashboard', function () {
    $tenant = createAvailabilityTenant();
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.availability')
        ->set('is_active', true)
        ->set('timezone', 'America/Chicago')
        ->set('days.1.enabled', true)
        ->set('days.1.start', '18:00')
        ->set('days.1.end', '22:00')
        ->set('days.6.enabled', true)
        ->set('days.6.start', '08:00')
        ->set('days.6.end', '14:00')
        ->set('note', 'Limpieza y mudanzas')
        ->set('hourly_rate', '25')
        ->call('save')
        ->assertHasNoErrors();

    $availability = TenantAvailability::firstWhere('tenant_id', 'availabilitytenant');

    expect($availability->is_active)->toBeTrue()
        ->and($availability->timezone)->toBe('America/Chicago')
        ->and($availability->sortedSlots()->all())->toBe([
            ['day' => 1, 'start' => '18:00', 'end' => '22:00'],
            ['day' => 6, 'start' => '08:00', 'end' => '14:00'],
        ])
        ->and($availability->hourly_rate)->toBe('25.00');
});

it('starts with availability switched on the first time', function () {
    $tenant = createAvailabilityTenant();
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.availability')->assertSet('is_active', true);
});

it('logs out from the panel navbar with a form instead of a component method', function () {
    $tenant = createAvailabilityTenant();
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.availability')
        ->assertSeeHtml('action="'.url('/logout').'"')
        ->assertDontSeeHtml('wire:click="logout"');
});

it('rejects a slot that ends before it starts', function () {
    $tenant = createAvailabilityTenant();
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.availability')
        ->set('days.2.enabled', true)
        ->set('days.2.start', '20:00')
        ->set('days.2.end', '09:00')
        ->call('save')
        ->assertHasErrors(['days.2.end' => 'after']);

    expect(TenantAvailability::count())->toBe(0);
});

it('shows the schedule on the public page of the tenant', function () {
    $tenant = createAvailabilityTenant();
    TenantAvailability::factory()->alwaysAvailable()->create(['tenant_id' => $tenant->id, 'note' => 'Jardinería los fines de semana']);
    tenancy()->initialize($tenant);

    Volt::test('tenant.welcome')
        ->assertSee(__('availability.public_heading'))
        ->assertSee(__('availability.public_now'))
        ->assertSee('Jardinería los fines de semana');
});

it('lists who is available now and who is available later today on the landing page', function () {
    $this->travelTo(Carbon::parse('2026-11-02 12:00', 'UTC'));

    foreach (['nowworker' => ['10:00', '16:00'], 'laterworker' => ['18:00', '22:00'], 'doneworker' => ['06:00', '09:00']] as $id => [$start, $end]) {
        createStoryTenant($id);
        TenantAvailability::factory()->create([
            'tenant_id' => $id,
            'timezone' => 'UTC',
            'slots' => [['day' => 1, 'start' => $start, 'end' => $end]],
        ]);
    }
    createStoryTenant('otherday');
    TenantAvailability::factory()->create(['tenant_id' => 'otherday', 'timezone' => 'UTC', 'slots' => [['day' => 3, 'start' => '10:00', 'end' => '16:00']]]);

    $availableToday = Volt::test('welcome-v2')
        ->assertSeeInOrder([
            __('welcome_v2.available.now_until', ['time' => '4:00 PM UTC']),
            __('welcome_v2.available.later_from', ['time' => '6:00 PM UTC']),
        ])
        ->viewData('availableToday');

    expect($availableToday->pluck('id')->all())->toBe(['nowworker', 'laterworker']);
});

it('shows a green dot on the stories of tenants available right now', function () {
    createStoryTenant('busy');
    createStoryTenant('free');
    TenantAvailability::factory()->alwaysAvailable()->create(['tenant_id' => 'free']);
    TenantAvailability::factory()->alwaysAvailable()->paused()->create(['tenant_id' => 'busy']);

    $spotlight = collect(Volt::test('welcome-v2')->assertSee('data-available-now', false)->get('spotlight'))->keyBy('id');

    expect($spotlight['free']['availableNow'])->toBeTrue()
        ->and($spotlight['busy']['availableNow'])->toBeFalse();
});
