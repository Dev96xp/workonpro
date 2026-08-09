<?php

use App\Models\Plan;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Database\Seeders\PlanSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');
    (new PlanSeeder)->run();
});

it('lists the seeded plans with their items', function () {
    Volt::test('admin.plans.index')
        ->assertSee('Básico')
        ->assertSee('Pro')
        ->assertSee('Enterprise')
        ->assertSee('3 / 3 planes activos');
});

it('blocks activating a 4th plan when 3 are already active', function () {
    $extra = Plan::create(['name' => 'Ultra', 'slug' => 'ultra', 'is_active' => false]);

    Volt::test('admin.plans.index')
        ->call('toggleActive', $extra->id)
        ->assertSet('limitError', fn ($value) => ! empty($value));

    expect($extra->fresh()->is_active)->toBeFalse();
});

it('lets a super admin deactivate a plan and then activate a different one', function () {
    $basic = Plan::where('slug', 'basic')->firstOrFail();
    $extra = Plan::create(['name' => 'Ultra', 'slug' => 'ultra', 'is_active' => false]);

    $component = Volt::test('admin.plans.index')
        ->call('toggleActive', $basic->id);

    expect($basic->fresh()->is_active)->toBeFalse();

    $component->call('toggleActive', $extra->id);

    expect($extra->fresh()->is_active)->toBeTrue();
});

it('does not let a super admin delete a plan that has tenants on it', function () {
    $basic = Plan::where('slug', 'basic')->firstOrFail();

    $tenant = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'usingbasic',
        'name' => 'Using Basic Co',
        'email' => 'usingbasic@example.com',
        'status' => 'active',
        'plan' => 'basic',
    ]));

    Volt::test('admin.plans.index')->call('deletePlan', $basic->id);

    expect(Plan::where('slug', 'basic')->exists())->toBeTrue();

    Tenant::withoutEvents(fn () => $tenant->delete());
});

it('lets a super admin create a new plan and redirects to its edit screen', function () {
    Volt::test('admin.plans.create')
        ->set('name', 'Ultra')
        ->set('sort_order', 4)
        ->call('save')
        ->assertRedirect(route('admin.plans.edit', Plan::where('slug', 'ultra')->firstOrFail()));

    $plan = Plan::where('slug', 'ultra')->first();

    expect($plan)->not->toBeNull()
        ->and($plan->name)->toBe('Ultra')
        ->and($plan->is_active)->toBeFalse();
});

it('blocks creating an already-active 4th plan past the limit', function () {
    Volt::test('admin.plans.create')
        ->set('name', 'Ultra')
        ->set('is_active', true)
        ->call('save')
        ->assertHasErrors('is_active');

    expect(Plan::where('slug', 'ultra')->exists())->toBeFalse();
});

it('lets a super admin add, edit, and remove plan items', function () {
    $plan = Plan::create(['name' => 'Ultra', 'slug' => 'ultra', 'sort_order' => 0, 'is_active' => false]);

    $component = Volt::test('admin.plans.edit', ['plan' => $plan])
        ->set('newItemName', 'images')
        ->set('newItemQuantity', '15')
        ->call('addItem')
        ->assertHasNoErrors();

    $item = $plan->items()->where('name', 'images')->firstOrFail();
    expect($item->quantity)->toBe(15);

    $component->set("itemQuantities.{$item->id}", '')
        ->call('saveItem', $item->id)
        ->assertHasNoErrors();

    expect($item->fresh()->quantity)->toBeNull();

    $component->call('removeItem', $item->id);

    expect($plan->items()->where('name', 'images')->exists())->toBeFalse();
});

it('rejects adding the same resource twice to a plan', function () {
    $plan = Plan::create(['name' => 'Ultra', 'slug' => 'ultra', 'sort_order' => 0, 'is_active' => false]);
    $plan->items()->create(['name' => 'images', 'quantity' => 10]);

    Volt::test('admin.plans.edit', ['plan' => $plan])
        ->set('newItemName', 'images')
        ->call('addItem')
        ->assertHasErrors('newItemName');

    expect($plan->items()->where('name', 'images')->count())->toBe(1);
});
