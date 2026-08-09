<?php

use App\Models\SuperAdmin;
use App\Models\Tenant;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('shows the config defaults when no override has been saved', function () {
    Volt::test('admin.plans.index')
        ->assertSet('limits.basic.images', '20')
        ->assertSet('limits.basic.services', '3')
        ->assertSet('limits.pro.services', '');
});

it('lets a super admin override a plan limit, and Tenant::planLimit picks it up', function () {
    Volt::test('admin.plans.index')
        ->set('limits.basic.images', '5')
        ->call('save', 'basic')
        ->assertHasNoErrors()
        ->assertSet('savedPlan', 'basic');

    expect(Tenant::planLimit('basic', 'images'))->toBe(5);
});

it('lets a super admin clear a limit to make it unlimited', function () {
    Volt::test('admin.plans.index')
        ->set('limits.basic.services', '')
        ->call('save', 'basic')
        ->assertHasNoErrors();

    expect(Tenant::planLimit('basic', 'services'))->toBeNull();
});

it('rejects a non-numeric limit', function () {
    Volt::test('admin.plans.index')
        ->set('limits.basic.images', 'abc')
        ->call('save', 'basic')
        ->assertHasErrors('limits.basic.images');

    expect(Tenant::planLimit('basic', 'images'))->toBe(20);
});

it('applies an admin-saved override even when read from inside a tenant context', function () {
    Volt::test('admin.plans.index')
        ->set('limits.pro.images', '7')
        ->call('save', 'pro')
        ->assertHasNoErrors();

    $tenant = Tenant::create([
        'id' => 'settingctx',
        'name' => 'Setting Ctx',
        'email' => 'settingctx@example.com',
        'status' => 'active',
        'plan' => 'pro',
    ]);

    tenancy()->initialize($tenant);

    expect(Tenant::planLimit('pro', 'images'))->toBe(7);

    tenancy()->end();
    $tenant->delete();
});
