<?php

use App\Models\Category;
use App\Models\ServiceListing;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Livewire\Volt\Volt;

function createDirectoryTenant(string $id, string $name, array $attributes = []): Tenant
{
    return Tenant::withoutEvents(fn () => Tenant::create([
        'id' => $id,
        'name' => $name,
        'email' => "{$id}@example.com",
        'status' => 'active',
        'plan' => 'basic',
        ...$attributes,
    ]));
}

it('hides the page from guests outside local environments', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get('/welcome-v2')->assertNotFound();
});

it('shows the page to a super admin outside local environments', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin')
        ->get('/welcome-v2')
        ->assertOk();
});

it('shows the active tenants count and directory', function () {
    createDirectoryTenant('zeta', 'Zeta Barber');
    createDirectoryTenant('alpha', 'Alpha Nails', ['plan' => 'pro', 'signup_city' => 'Miami']);
    createDirectoryTenant('pending', 'Pending Shop', ['status' => 'pending']);

    $this->get('/welcome-v2')
        ->assertOk()
        ->assertSee('negocios ya están en Workon')
        ->assertSeeInOrder(['Alpha Nails', 'Zeta Barber'])
        ->assertSee('Miami')
        ->assertDontSee('Pending Shop');
});

it('filters the directory by category', function () {
    Category::create(['name' => 'Barbería', 'slug' => 'barber', 'sort_order' => 1, 'is_active' => true]);
    createDirectoryTenant('zeta', 'Zeta Barber');
    createDirectoryTenant('alpha', 'Alpha Nails');
    Tenant::query()->whereKey('alpha')->update(['created_at' => now()->subMonth()]);
    ServiceListing::create(['tenant_id' => 'zeta', 'service_id' => 1, 'name' => 'Corte', 'price' => 10, 'category' => 'barber', 'is_active' => true]);

    Volt::test('welcome-v2')
        ->assertSee('Alpha Nails')
        ->set('category', 'barber')
        ->assertSee('Zeta Barber')
        ->assertDontSee('Alpha Nails');
});

it('searches the directory by business name or city', function () {
    createDirectoryTenant('zeta', 'Zeta Barber', ['signup_city' => 'Orlando']);
    createDirectoryTenant('alpha', 'Alpha Nails', ['signup_city' => 'Miami']);
    Tenant::query()->update(['created_at' => now()->subMonth()]);

    Volt::test('welcome-v2')
        ->set('search', 'zeta')
        ->assertSee('Zeta Barber')
        ->assertDontSee('Alpha Nails')
        ->set('search', 'miami')
        ->assertSee('Alpha Nails')
        ->assertDontSee('Zeta Barber')
        ->set('search', 'nothing-matches')
        ->assertSee('nothing-matches');
});

it('keeps the same hero image for the whole day and changes it on another day', function () {
    foreach (range(1, 6) as $number) {
        createDirectoryTenant("hero{$number}", "Hero {$number}", ['featured_image_url' => "https://example.com/hero{$number}.webp"]);
    }

    $this->travelTo('2026-10-29 08:00');
    $morning = Volt::test('welcome-v2')->get('heroImage');
    $this->travelTo('2026-10-29 22:00');
    $night = Volt::test('welcome-v2')->get('heroImage');

    $imagesAcrossWeek = collect(range(1, 7))
        ->map(function (int $day) {
            $this->travelTo("2026-11-0{$day} 12:00");

            return Volt::test('welcome-v2')->get('heroImage');
        })
        ->unique();

    expect($morning)->not->toBeNull()->toBe($night)
        ->and($imagesAcrossWeek->count())->toBeGreaterThan(1);
});

it('shows tenants with a featured image in the stories row', function () {
    createDirectoryTenant('photo', 'Photo Studio', ['featured_image_url' => 'https://example.com/photo.webp']);

    $this->get('/welcome-v2')->assertSee('https://example.com/photo.webp');
});
