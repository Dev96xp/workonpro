<?php

use App\Models\Category;
use App\Models\Subcategory;
use Database\Seeders\CategorySeeder;

it('seeds the main categories with their subcategories in order', function () {
    (new CategorySeeder)->run();

    $subcategoryCount = collect(CategorySeeder::CATEGORIES)->sum(fn (array $category) => count($category['subcategories']));

    expect(Category::count())->toBe(20)
        ->and(Category::orderBy('sort_order')->pluck('slug')->all())->toBe(array_keys(CategorySeeder::CATEGORIES))
        ->and(Subcategory::count())->toBe($subcategoryCount)
        ->and(Category::firstWhere('slug', 'construction')->subcategories)->toHaveCount(28)
        ->and(Category::firstWhere('slug', 'technology')->subcategories->pluck('slug'))->toContain('robotics')
        ->and(Subcategory::firstWhere('slug', 'foundation')->only(['name', 'name_en', 'is_active']))
        ->toBe(['name' => 'Cimentación', 'name_en' => 'Foundation', 'is_active' => true]);

    Category::where('slug', '!=', 'construction')->withCount('subcategories')->get()
        ->each(fn (Category $category) => expect($category->subcategories_count)->toBe(5));
});

it('keeps the names and switches edited in the admin when seeding again', function () {
    (new CategorySeeder)->run();
    Subcategory::where('slug', 'roofing')->update(['name' => 'Techos y tejados', 'is_active' => false]);
    Category::where('slug', 'pets')->update(['name' => 'Animales']);

    (new CategorySeeder)->run();

    expect(Subcategory::firstWhere('slug', 'roofing')->only(['name', 'is_active']))
        ->toBe(['name' => 'Techos y tejados', 'is_active' => false])
        ->and(Category::firstWhere('slug', 'pets')->name)->toBe('Animales')
        ->and(Category::count())->toBe(20);
});
