<?php

use App\Models\Category;
use App\Models\ServiceListing;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');
});

it('lets a super admin create a category with an auto-generated slug', function () {
    Volt::test('admin.categories.create')
        ->set('name', 'Cerrajería')
        ->set('sort_order', 5)
        ->call('save')
        ->assertRedirect(route('admin.categories.index'));

    $category = Category::where('slug', 'cerrajeria')->first();

    expect($category)->not->toBeNull()
        ->and($category->name)->toBe('Cerrajería')
        ->and($category->sort_order)->toBe(5)
        ->and($category->is_active)->toBeTrue();
});

it('lets a super admin rename a category without changing its slug', function () {
    $category = Category::create(['name' => 'Test', 'slug' => 'test', 'sort_order' => 0, 'is_active' => true]);

    Volt::test('admin.categories.edit', ['category' => $category])
        ->set('name', 'Test Renombrada')
        ->set('is_active', false)
        ->call('save')
        ->assertRedirect(route('admin.categories.index'));

    $category->refresh();

    expect($category->name)->toBe('Test Renombrada')
        ->and($category->slug)->toBe('test')
        ->and($category->is_active)->toBeFalse();
});

it('excludes deactivated categories from the central search filter', function () {
    Category::create(['name' => 'Inactiva', 'slug' => 'inactiva', 'sort_order' => 0, 'is_active' => false]);

    Volt::test('search.index')->assertDontSee('Inactiva');
});

it('does not delete a category that has an associated service listing', function () {
    $tenant = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'guardtenant',
        'name' => 'Guard Co',
        'email' => 'guard@example.com',
        'status' => 'active',
        'plan' => 'pro',
    ]));

    ServiceListing::create([
        'tenant_id' => $tenant->id,
        'service_id' => 1,
        'name' => 'Reparación de tubería',
        'category' => 'plumbing',
        'is_active' => true,
    ]);

    $category = Category::where('slug', 'plumbing')->firstOrFail();

    Volt::test('admin.categories.index')->call('deleteCategory', $category->id);

    expect(Category::where('slug', 'plumbing')->exists())->toBeTrue();

    Tenant::withoutEvents(fn () => $tenant->delete());
});
