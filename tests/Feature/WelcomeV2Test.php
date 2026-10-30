<?php

use App\Models\Category;
use App\Models\ServiceListing;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
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

function listTenantInCategory(string $tenantId, string $categorySlug): void
{
    ServiceListing::create(['tenant_id' => $tenantId, 'service_id' => ServiceListing::query()->where('tenant_id', $tenantId)->count() + 1, 'name' => 'Service', 'price' => 10, 'category' => $categorySlug, 'is_active' => true]);
}

it('greets the visitor with the city cloudflare resolved', function () {
    Http::fake();

    $this->withHeader('CF-IPCity', 'Atlanta')
        ->get('/welcome-v2')
        ->assertSee('Hola, Atlanta');

    Http::assertNothingSent();
});

it('looks up the city from the real visitor ip behind cloudflare', function () {
    config(['services.ipinfo.token' => 'fake-token']);
    Http::fake(['ipinfo.io/8.8.8.8*' => Http::response(['city' => 'Omaha'])]);

    $this->withHeader('CF-Connecting-IP', '8.8.8.8')
        ->get('/welcome-v2')
        ->assertSee('Hola, Omaha');
});

it('hides the city greeting when the location is unknown', function () {
    $this->get('/welcome-v2')->assertDontSee('📍');
});

it('counts only active tenants', function () {
    createDirectoryTenant('zeta', 'Zeta Barber');
    createDirectoryTenant('pending', 'Pending Shop', ['status' => 'pending']);

    Volt::test('welcome-v2')
        ->assertViewHas('tenantsCount', 1)
        ->assertDontSee('Pending Shop');
});

it('shows one band per category with the tenants listed in it', function () {
    Category::updateOrCreate(['slug' => 'construction'], ['name' => 'Construcción', 'sort_order' => 1, 'is_active' => true]);
    Category::updateOrCreate(['slug' => 'cleaning'], ['name' => 'Cleaning', 'sort_order' => 2, 'is_active' => true]);
    Category::updateOrCreate(['slug' => 'framing'], ['name' => 'Framing', 'sort_order' => 3, 'is_active' => true]);
    createDirectoryTenant('build', 'Build Co');
    createDirectoryTenant('shine', 'Shine Cleaners');
    createDirectoryTenant('loner', 'No Category Shop');
    Tenant::query()->update(['created_at' => now()->subMonth()]);
    listTenantInCategory('build', 'construction');
    listTenantInCategory('build', 'cleaning');
    listTenantInCategory('shine', 'cleaning');

    $bands = Volt::test('welcome-v2')
        ->assertSeeInOrder(['Build Co', 'Build Co', 'Shine Cleaners'])
        ->assertDontSee('No Category Shop')
        ->viewData('categoryBands');

    expect($bands->pluck('slug')->all())->toBe(['construction', 'cleaning'])
        ->and(collect($bands[1]['tenants'])->pluck('name')->all())->toBe(['Build Co', 'Shine Cleaners']);
});

it('shows each tenant only once in its category band', function () {
    Category::updateOrCreate(['slug' => 'framing'], ['name' => 'Framing', 'sort_order' => 1, 'is_active' => true]);
    createDirectoryTenant('frame', 'Solo Framer');
    Tenant::query()->update(['created_at' => now()->subMonth()]);
    listTenantInCategory('frame', 'framing');

    $html = Volt::test('welcome-v2')->html();

    expect(substr_count($html, '>Solo Framer</span>'))->toBe(1);
});

it('searches the category bands by business name or city', function () {
    Category::updateOrCreate(['slug' => 'barber'], ['name' => 'Barbería', 'sort_order' => 1, 'is_active' => true]);
    createDirectoryTenant('zeta', 'Zeta Barber', ['signup_city' => 'Orlando']);
    createDirectoryTenant('alpha', 'Alpha Nails', ['signup_city' => 'Miami']);
    Tenant::query()->update(['created_at' => now()->subMonth()]);
    listTenantInCategory('zeta', 'barber');
    listTenantInCategory('alpha', 'barber');

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
