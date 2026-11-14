<?php

use App\Models\BusinessImage;
use App\Models\BusinessProfile;
use App\Models\Coupon;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Volt;

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    Tenant::query()->where('id', 'onboardingtenant')->get()->each->delete();
});

function initializeOnboardingTenant(array $attributes = []): Tenant
{
    $tenant = Tenant::create([
        'id' => 'onboardingtenant',
        'name' => 'Onboarding Co',
        'email' => 'onboarding@example.com',
        'status' => 'active',
        'plan' => 'basic',
        ...$attributes,
    ]);
    $tenant->domains()->create(['domain' => 'onboardingtenant']);
    tenancy()->initialize($tenant);
    test()->actingAs(User::factory()->create());

    return $tenant;
}

it('opens the guide on the dashboard for a new tenant, once per session', function () {
    initializeOnboardingTenant();

    Volt::test('tenant.onboarding-guide')
        ->assertSet('showGuide', true)
        ->assertSeeHtml('data-onboarding-indicator')
        ->assertSee('Your page: 0/5 ready')
        ->assertSee('Create a service')
        ->assertSee('Very important')
        ->assertSeeHtml('data-onboarding-tip')
        ->assertSee('tap your business name in the top left corner');

    Volt::test('tenant.onboarding-guide')->assertSet('showGuide', false);

    Volt::test('tenant.dashboard')->assertSeeLivewire('tenant.onboarding-guide');
});

it('marks each step done from the real data of the tenant', function () {
    initializeOnboardingTenant();

    BusinessProfile::create(['business_name' => 'Onboarding Co', 'description' => 'We build decks.', 'phone' => '402-555-0101']);
    $image = BusinessImage::create(['filename' => 'a.jpg', 'original_name' => 'a.jpg', 'path' => 'tenants/a.jpg', 'mime_type' => 'image/jpeg', 'size' => 10, 'compressed_size' => 8]);
    Service::create(['name' => 'Deck repair', 'price' => 100, 'is_active' => true]);

    $component = Volt::test('tenant.onboarding-guide')->assertSee('Your page: 3/5 ready');
    expect(collect($component->viewData('steps'))->pluck('done', 'key')->all())->toBe([
        'profile' => true,
        'images' => true,
        'featured' => false,
        'service' => true,
        'coupon' => false,
    ]);

    BusinessImage::setFeatured($image->id);
    Coupon::create(['code' => 'WELCOME10', 'type' => 'percentage', 'value' => 10, 'is_active' => true]);

    Volt::test('tenant.onboarding-guide')
        ->assertSet('showGuide', false)
        ->assertDontSeeHtml('data-onboarding-indicator');
});

it('counts owner logins and stops showing after five of them', function () {
    $tenant = initializeOnboardingTenant();
    $user = User::factory()->create();

    foreach (range(1, 5) as $login) {
        Auth::login($user);
    }

    expect($tenant->fresh()->onboarding_logins)->toBe(5)
        ->and($tenant->fresh()->showsOnboardingGuide())->toBeTrue();

    Auth::login($user);

    expect(tenant()->showsOnboardingGuide())->toBeFalse();

    Volt::test('tenant.onboarding-guide')
        ->assertSet('showGuide', false)
        ->assertDontSeeHtml('data-onboarding-indicator');
});

it('does not count logins outside a tenant', function () {
    $tenant = initializeOnboardingTenant();
    tenancy()->end();

    Auth::login(User::factory()->create());

    expect($tenant->fresh()->onboarding_logins)->toBeNull();
});

it('hides the guide for good with "don\'t show again"', function () {
    initializeOnboardingTenant();

    Volt::test('tenant.onboarding-guide')
        ->call('dismissForever')
        ->assertSet('showGuide', false)
        ->assertDontSeeHtml('data-onboarding-indicator');

    expect(tenant()->showsOnboardingGuide())->toBeFalse();
});

it('lets the tenant turn the guide back on from settings', function () {
    initializeOnboardingTenant(['onboarding_logins' => 12]);

    Volt::test('tenant.settings')
        ->assertSet('showOnboardingGuide', false)
        ->assertSee('Show the getting started guide')
        ->set('showOnboardingGuide', true);

    expect(tenant()->showsOnboardingGuide())->toBeTrue()
        ->and(tenant()->onboarding_logins)->toBe(0);

    Volt::test('tenant.onboarding-guide')->assertSet('showGuide', true);

    Volt::test('tenant.settings')->set('showOnboardingGuide', false);

    expect(tenant()->showsOnboardingGuide())->toBeFalse();
});

it('shows the guide in spanish', function () {
    initializeOnboardingTenant();
    app()->setLocale('es');

    Volt::test('tenant.onboarding-guide')
        ->assertSee('Deja tu página lista')
        ->assertSee('Crea un servicio');
});
