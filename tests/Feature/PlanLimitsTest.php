<?php

use App\Models\BusinessImage;
use App\Models\Coupon;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Volt\Volt;

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

function createPlanTenant(string $id, string $plan): Tenant
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

it('resolves plan limits from config, with null meaning unlimited', function () {
    expect(Tenant::planLimit('basic', 'services'))->toBe(3)
        ->and(Tenant::planLimit('basic', 'coupons'))->toBe(2)
        ->and(Tenant::planLimit('basic', 'images'))->toBe(20)
        ->and(Tenant::planLimit('pro', 'services'))->toBeNull()
        ->and(Tenant::planLimit('pro', 'images'))->toBe(100)
        ->and(Tenant::planLimit('enterprise', 'images'))->toBeNull();
});

it('reports whether a tenant is within its plan limit, treating null as unlimited', function () {
    expect(Tenant::withinPlanLimit('basic', 'services', 2))->toBeTrue()
        ->and(Tenant::withinPlanLimit('basic', 'services', 3))->toBeFalse()
        ->and(Tenant::withinPlanLimit('pro', 'services', 999))->toBeTrue();
});

it('blocks a basic tenant from creating a 4th service', function () {
    $tenant = createPlanTenant('basicservices', 'basic');

    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    for ($i = 1; $i <= 3; $i++) {
        Service::create(['name' => "Servicio {$i}", 'category' => 'general', 'is_active' => true]);
    }

    Volt::test('tenant.services')
        ->call('openCreate')
        ->assertHasErrors('name')
        ->assertSet('showModal', false);

    expect(Service::count())->toBe(3);

    tenancy()->end();
    $tenant->delete();
});

it('lets a pro tenant create more than 3 services', function () {
    $tenant = createPlanTenant('proservices', 'pro');

    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    for ($i = 1; $i <= 5; $i++) {
        Service::create(['name' => "Servicio {$i}", 'category' => 'general', 'is_active' => true]);
    }

    Volt::test('tenant.services')
        ->call('openCreate')
        ->assertHasNoErrors()
        ->assertSet('showModal', true);

    tenancy()->end();
    $tenant->delete();
});

it('blocks a basic tenant from creating a 3rd coupon', function () {
    $tenant = createPlanTenant('basiccoupons', 'basic');

    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Coupon::create(['code' => 'PROMO1', 'type' => 'percentage', 'value' => 10, 'is_active' => true]);
    Coupon::create(['code' => 'PROMO2', 'type' => 'percentage', 'value' => 10, 'is_active' => true]);

    Volt::test('tenant.coupons')
        ->call('openCreate')
        ->assertHasErrors('code')
        ->assertSet('showModal', false);

    expect(Coupon::count())->toBe(2);

    tenancy()->end();
    $tenant->delete();
});

it('lets an enterprise tenant create unlimited coupons', function () {
    $tenant = createPlanTenant('entcoupons', 'enterprise');

    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    for ($i = 1; $i <= 4; $i++) {
        Coupon::create(['code' => "PROMO{$i}", 'type' => 'percentage', 'value' => 10, 'is_active' => true]);
    }

    Volt::test('tenant.coupons')
        ->call('openCreate')
        ->assertHasNoErrors()
        ->assertSet('showModal', true);

    tenancy()->end();
    $tenant->delete();
});

it('stops a basic tenant from uploading past the image limit', function () {
    $tenant = createPlanTenant('basicimages', 'basic');

    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    for ($i = 1; $i <= 20; $i++) {
        BusinessImage::create([
            'filename' => "img{$i}.webp",
            'original_name' => "img{$i}.webp",
            'path' => "tenants/basicimages/images/img{$i}.webp",
            'mime_type' => 'image/webp',
            'size' => 100,
            'compressed_size' => 100,
        ]);
    }

    Volt::test('tenant.images')
        ->assertViewHas('canUpload', false)
        ->assertViewHas('limit', 20)
        ->assertViewHas('imageCount', 20);

    tenancy()->end();
    $tenant->delete();
});
