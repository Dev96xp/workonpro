<?php

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\ServiceListing;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;
use App\Services\IpGeolocationService;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

beforeEach(function () {
    seedSignupPlans();
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    Tenant::query()->whereIn('id', ['locationsignup', 'locationtenant'])->get()->each->delete();
});

it('prefills the signup location from the visitor ip', function () {
    $this->mock(IpGeolocationService::class, function ($mock) {
        $mock->shouldReceive('location')->andReturn(['city' => 'Omaha', 'state' => 'Nebraska', 'country' => 'US']);
    });

    Volt::test('register.create')
        ->assertSet('city', 'Omaha')
        ->assertSet('state', 'Nebraska')
        ->assertSet('country', 'United States');
});

it('requires city, state and country at signup and does not ask for the street', function () {
    Volt::test('register.create')
        ->assertDontSee(__('register.create.street_label'))
        ->set('plan', 'basic')
        ->set('city', '')
        ->set('state', '')
        ->set('country', '')
        ->call('checkout')
        ->assertHasErrors(['city' => 'required', 'state' => 'required', 'country' => 'required']);
});

it('stores the full location when a tenant registers', function () {
    Volt::test('register.create')
        ->set('plan', 'basic')
        ->set('business_name', 'Location Co')
        ->set('subdomain', 'locationsignup')
        ->set('email', 'locationsignup@example.com')
        ->set('city', 'Omaha')
        ->set('state', 'Nebraska')
        ->set('country', 'United States')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('accepted_terms', true)
        ->call('checkout')
        ->assertHasNoErrors();

    $tenant = Tenant::find('locationsignup');

    expect($tenant->only(['street_address', 'city', 'state', 'country']))->toBe([
        'street_address' => null,
        'city' => 'Omaha',
        'state' => 'Nebraska',
        'country' => 'United States',
    ]);
});

it('fills settings with the signup location and keeps the central record in sync', function () {
    $tenant = Tenant::create([
        'id' => 'locationtenant',
        'name' => 'Location Co',
        'email' => 'locationtenant@example.com',
        'status' => 'active',
        'plan' => 'basic',
        'street_address' => '123 Main St',
        'city' => 'Omaha',
        'state' => 'Nebraska',
        'country' => 'United States',
    ]);
    $tenant->domains()->create(['domain' => 'locationtenant']);

    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.settings')
        ->assertSet('address', '123 Main St')
        ->assertSet('city', 'Omaha')
        ->assertSet('state', 'Nebraska')
        ->set('city', 'Lincoln')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($tenant->fresh()->city)->toBe('Lincoln')
        ->and(BusinessProfile::first()->city)->toBe('Lincoln');
});

it('keeps the street address of personal accounts off the public page', function () {
    $tenant = Tenant::create([
        'id' => 'locationtenant',
        'name' => 'María López',
        'email' => 'locationtenant@example.com',
        'status' => 'active',
        'plan' => 'basic',
        'account_type' => 'personal',
    ]);
    $tenant->domains()->create(['domain' => 'locationtenant']);

    tenancy()->initialize($tenant);
    BusinessProfile::create(['business_name' => 'María López', 'address' => '742 Evergreen Terrace', 'city' => 'Omaha']);

    Volt::test('tenant.welcome')
        ->assertDontSee('742 Evergreen Terrace')
        ->assertSee('Omaha');
});

it('translates the public tenant page labels to the current locale', function () {
    $tenant = Tenant::create([
        'id' => 'locationtenant',
        'name' => 'Location Co',
        'email' => 'locationtenant@example.com',
        'status' => 'active',
        'plan' => 'basic',
    ]);
    $tenant->domains()->create(['domain' => 'locationtenant']);

    tenancy()->initialize($tenant);
    BusinessProfile::create(['business_name' => 'Location Co', 'has_license' => true, 'has_insurance' => true]);

    app()->setLocale('en');
    Volt::test('tenant.welcome')
        ->assertSee(['Licensed', 'Insured', 'Photos coming soon'])
        ->assertDontSee(['Con licencia', 'Con seguro', 'Pronto habrá fotos aquí']);

    app()->setLocale('es');
    Volt::test('tenant.welcome')
        ->assertSee(['Con licencia', 'Con seguro', 'Pronto habrá fotos aquí']);
});

it('links the powered by footer of the tenant page to the main platform', function () {
    $tenant = Tenant::create([
        'id' => 'locationtenant',
        'name' => 'Location Co',
        'email' => 'locationtenant@example.com',
        'status' => 'active',
        'plan' => 'basic',
    ]);
    $tenant->domains()->create(['domain' => 'locationtenant']);

    tenancy()->initialize($tenant);

    Volt::test('tenant.welcome')
        ->assertSeeHtml('href="'.config('app.url').'" target="_blank" rel="noopener"')
        ->assertSeeHtml('data-platform-link>Workon</a>');
});

it('shows tenants from the visitor city first on the landing page', function () {
    Http::fake();
    Category::updateOrCreate(['slug' => 'cleaning'], ['name' => 'Cleaning', 'sort_order' => 1, 'is_active' => true]);

    foreach (['alpha' => 'Miami', 'zeta' => 'Omaha'] as $id => $city) {
        Tenant::withoutEvents(fn () => Tenant::create([
            'id' => $id,
            'name' => ucfirst($id).' Cleaners',
            'email' => "{$id}@example.com",
            'status' => 'active',
            'plan' => 'basic',
            'city' => $city,
        ]));
        ServiceListing::create(['tenant_id' => $id, 'service_id' => 1, 'name' => 'Cleaning', 'price' => 10, 'category' => 'cleaning', 'is_active' => true]);
    }
    Tenant::query()->update(['created_at' => now()->subMonth()]);

    $this->withHeader('CF-IPCity', 'Omaha')
        ->get('/')
        ->assertSeeInOrder(['Zeta Cleaners', 'Alpha Cleaners']);

    $this->withHeader('CF-IPCity', 'Miami')
        ->get('/')
        ->assertSeeInOrder(['Alpha Cleaners', 'Zeta Cleaners']);
});

it('shows the city the tenant typed in the admin list, not only the ip city', function () {
    Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'locationtenant',
        'name' => 'Location Co',
        'email' => 'locationtenant@example.com',
        'status' => 'active',
        'plan' => 'basic',
        'city' => 'Omaha',
        'state' => 'Nebraska',
        'signup_city' => 'Council Bluffs',
    ]));

    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');

    Volt::test('admin.tenants.index')
        ->assertSee('Omaha, Nebraska')
        ->assertSee('IP: Council Bluffs');
});
