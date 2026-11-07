<?php

use App\Models\BusinessProfile;
use App\Models\Service;
use App\Models\ServiceListing;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    (new CategorySeeder)->run();
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

function createTenantWithService(string $id, string $city, string $subcategorySlug, string $name): Tenant
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
        'name' => $name,
        'subcategory' => $subcategorySlug,
        'price' => 100,
        'is_active' => true,
    ]);
    tenancy()->end();

    return $tenant;
}

it('syncs a service into the central index on create, update, and delete', function () {
    $tenant = createTenantWithService('synctenant', 'Miami', 'roofing', 'Reparación de techos');

    tenancy()->initialize($tenant);
    $service = Service::first();
    tenancy()->end();

    $listing = ServiceListing::where('tenant_id', $tenant->id)->where('service_id', $service->id)->first();

    expect($listing)->not->toBeNull()
        ->and($listing->name)->toBe('Reparación de techos')
        ->and($listing->subcategory)->toBe('roofing')
        ->and($listing->category)->toBe('construction')
        ->and($listing->city)->toBe('Miami')
        ->and((float) $listing->price)->toBe(100.0);

    tenancy()->initialize($tenant);
    $service->update(['price' => 250]);
    tenancy()->end();

    expect((float) $listing->fresh()->price)->toBe(250.0);

    tenancy()->initialize($tenant);
    $service->delete();
    tenancy()->end();

    expect(ServiceListing::where('tenant_id', $tenant->id)->where('service_id', $service->id)->exists())->toBeFalse();

    $tenant->delete();
});

it('syncs the business phone into the central listing and shows it on the search page', function () {
    $tenant = Tenant::create([
        'id' => 'phonetenant',
        'name' => 'Business phonetenant',
        'email' => 'phonetenant@example.com',
        'status' => 'active',
        'plan' => 'pro',
    ]);
    $tenant->domains()->create(['domain' => 'phonetenant']);

    tenancy()->initialize($tenant);
    BusinessProfile::create(['business_name' => 'Business phonetenant', 'city' => 'Denver', 'phone' => '555-0100']);
    Service::create(['name' => 'Plomería de emergencia', 'subcategory' => 'plumbing-repair', 'price' => 80, 'is_active' => true]);
    tenancy()->end();

    $listing = ServiceListing::where('tenant_id', 'phonetenant')->firstOrFail();

    expect($listing->phone)->toBe('555-0100');

    Volt::test('search.index')->assertSee('555-0100');

    $tenant->delete();
});

it('filters central search results by category and city', function () {
    $electric = createTenantWithService('electrictenant', 'Austin', 'electrical-repair', 'Instalación eléctrica');
    $paint = createTenantWithService('painttenant', 'Miami', 'painting', 'Pintura de interiores');

    Volt::test('search.index')
        ->assertSee('Instalación eléctrica')
        ->assertSee('Pintura de interiores')
        ->set('category', 'maintenance')
        ->assertSee('Instalación eléctrica')
        ->assertDontSee('Pintura de interiores')
        ->set('category', 'painting')
        ->assertSee('Pintura de interiores')
        ->assertDontSee('Instalación eléctrica')
        ->set('category', '')
        ->set('city', 'Miami')
        ->assertSee('Pintura de interiores')
        ->assertDontSee('Instalación eléctrica');

    $electric->delete();
    $paint->delete();
});

it('lets a tenant pick a subcategory grouped by category for a service', function () {
    $tenant = Tenant::create(['id' => 'pickertenant', 'name' => 'Picker Co', 'email' => 'picker@example.com', 'status' => 'active', 'plan' => 'pro']);
    $tenant->domains()->create(['domain' => 'pickertenant']);

    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.services')
        ->call('openCreate')
        ->assertSee('Construction')
        ->assertSee('Foundation')
        ->set('name', 'Losas de concreto')
        ->set('subcategory', 'not-a-subcategory')
        ->call('save')
        ->assertHasErrors('subcategory')
        ->set('subcategory', 'foundation')
        ->call('save')
        ->assertHasNoErrors();

    expect(Service::first()->subcategory)->toBe('foundation');
    tenancy()->end();

    expect(ServiceListing::where('tenant_id', 'pickertenant')->first()->only(['category', 'subcategory']))
        ->toBe(['category' => 'construction', 'subcategory' => 'foundation']);

    $tenant->delete();
});
