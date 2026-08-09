<?php

use App\Models\BusinessImage;
use App\Models\ServiceListing;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Volt\Volt;

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('syncs the featured image url to the central tenant record when an image is marked as featured', function () {
    $tenant = Tenant::create([
        'id' => 'featuredsync',
        'name' => 'Featured Sync Co',
        'email' => 'featuredsync@example.com',
        'status' => 'active',
        'plan' => 'pro',
    ]);
    $tenant->domains()->create(['domain' => 'featuredsync']);

    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $image = BusinessImage::create([
        'filename' => 'photo.webp',
        'original_name' => 'photo.webp',
        'path' => 'tenants/featuredsync/images/photo.webp',
        'mime_type' => 'image/webp',
        'size' => 100,
        'compressed_size' => 100,
    ]);

    Volt::test('tenant.images')->call('setFeatured', $image->id);

    tenancy()->end();

    expect($tenant->fresh()->featured_image_url)->toBe($image->url());

    $tenant->delete();
});

it('clears the central featured image url when the featured image is deleted', function () {
    $tenant = Tenant::create([
        'id' => 'featureddelete',
        'name' => 'Featured Delete Co',
        'email' => 'featureddelete@example.com',
        'status' => 'active',
        'plan' => 'pro',
    ]);
    $tenant->domains()->create(['domain' => 'featureddelete']);

    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $image = BusinessImage::create([
        'filename' => 'photo.webp',
        'original_name' => 'photo.webp',
        'path' => 'tenants/featureddelete/images/photo.webp',
        'mime_type' => 'image/webp',
        'size' => 100,
        'compressed_size' => 100,
    ]);

    $component = Volt::test('tenant.images')->call('setFeatured', $image->id);

    expect($tenant->fresh()->featured_image_url)->not->toBeNull();

    $component->set('deletingId', $image->id)->call('delete');

    tenancy()->end();

    expect($tenant->fresh()->featured_image_url)->toBeNull();

    $tenant->delete();
});

it('shows the tenant featured image as the search card background', function () {
    $tenant = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'featuredsearch',
        'name' => 'Featured Search Co',
        'email' => 'featuredsearch@example.com',
        'status' => 'active',
        'plan' => 'pro',
        'featured_image_url' => 'https://workonpro.test/storage/tenants/featuredsearch/images/hero.webp',
    ]));

    ServiceListing::create([
        'tenant_id' => $tenant->id,
        'service_id' => 1,
        'name' => 'Reparación de techos',
        'category' => 'roofing',
        'is_active' => true,
    ]);

    Volt::test('search.index')->assertSee('https://workonpro.test/storage/tenants/featuredsearch/images/hero.webp', false);

    $tenant->delete();
});
