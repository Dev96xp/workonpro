<?php

use App\Models\Category;
use App\Models\ServiceListing;
use App\Models\Subcategory;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');
});

function listServiceIn(Subcategory $subcategory): Tenant
{
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
        'category' => $subcategory->category->slug,
        'subcategory' => $subcategory->slug,
        'is_active' => true,
    ]);

    return $tenant;
}

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
    $category = Category::factory()->create(['name' => 'Test', 'slug' => 'test']);

    Volt::test('admin.categories.edit', ['category' => $category])
        ->set('name', 'Test Renombrada')
        ->set('name_en', 'Renamed Test')
        ->set('is_active', false)
        ->call('save')
        ->assertRedirect(route('admin.categories.index'));

    $category->refresh();

    expect($category->name)->toBe('Test Renombrada')
        ->and($category->name_en)->toBe('Renamed Test')
        ->and($category->slug)->toBe('test')
        ->and($category->is_active)->toBeFalse();
});

it('lists each category with its subcategories', function () {
    $category = Category::factory()->create(['name' => 'Construcción']);
    Subcategory::factory()->for($category)->create(['name' => 'Cimentación']);

    Volt::test('admin.categories.index')
        ->assertSee('Construcción')
        ->assertSee('Cimentación')
        ->assertSee('1 subcategorías');
});

it('lets a super admin add a subcategory to a category', function () {
    $category = Category::factory()->create();

    Volt::test('admin.subcategories.create', ['category' => $category])
        ->set('name', 'Cerrajería')
        ->set('name_en', 'Locksmith')
        ->call('save')
        ->assertRedirect(route('admin.categories.index'));

    expect($category->subcategories()->first()->only(['name', 'name_en', 'slug']))
        ->toBe(['name' => 'Cerrajería', 'name_en' => 'Locksmith', 'slug' => 'locksmith']);
});

it('moves a subcategory and its listed services to another category', function () {
    $subcategory = Subcategory::factory()->create(['slug' => 'plumbing-repair']);
    $maintenance = Category::factory()->create(['slug' => 'maintenance']);
    $tenant = listServiceIn($subcategory);

    Volt::test('admin.subcategories.edit', ['subcategory' => $subcategory])
        ->set('category_id', $maintenance->id)
        ->set('name', 'Plomería')
        ->call('save')
        ->assertHasNoErrors();

    expect($subcategory->fresh()->category_id)->toBe($maintenance->id)
        ->and(ServiceListing::first()->category)->toBe('maintenance');

    Tenant::withoutEvents(fn () => $tenant->delete());
});

it('excludes deactivated categories and subcategories from the central search filter', function () {
    Category::factory()->inactive()->create(['name' => 'Inactiva']);
    Subcategory::factory()->inactive()->create(['name' => 'Apagada']);

    Volt::test('search.index')->assertDontSee('Inactiva')->assertDontSee('Apagada');
});

it('does not delete a subcategory used by a service, nor a category that has subcategories', function () {
    $subcategory = Subcategory::factory()->create();
    $tenant = listServiceIn($subcategory);

    Volt::test('admin.categories.index')
        ->call('deleteSubcategory', $subcategory->id)
        ->call('deleteCategory', $subcategory->category_id);

    expect(Subcategory::whereKey($subcategory->id)->exists())->toBeTrue()
        ->and(Category::whereKey($subcategory->category_id)->exists())->toBeTrue();

    Tenant::withoutEvents(fn () => $tenant->delete());
});

it('deletes an unused subcategory and then its empty category', function () {
    $subcategory = Subcategory::factory()->create();

    Volt::test('admin.categories.index')
        ->call('deleteSubcategory', $subcategory->id)
        ->call('deleteCategory', $subcategory->category_id);

    expect(Subcategory::count())->toBe(0)
        ->and(Category::count())->toBe(0);
});
