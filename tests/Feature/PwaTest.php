<?php

use App\Http\Controllers\PwaManifestController;
use App\Models\Tenant;

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    Tenant::query()->where('id', 'pwatenant')->get()->each->delete();
});

it('serves an installable manifest for the main platform', function () {
    $this->get('/manifest.webmanifest')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJson([
            'name' => config('app.name'),
            'start_url' => '/',
            'display' => 'standalone',
        ])
        ->assertJsonCount(3, 'icons')
        ->assertJsonPath('icons.2.purpose', 'maskable');
});

it('names the manifest after the business on a tenant subdomain', function () {
    $tenant = Tenant::create([
        'id' => 'pwatenant',
        'name' => 'Perla Rodriguez',
        'email' => 'pwatenant@example.com',
        'status' => 'active',
        'plan' => 'basic',
    ]);
    $tenant->domains()->create(['domain' => 'pwatenant']);

    tenancy()->initialize($tenant);

    $manifest = (new PwaManifestController)()->getData(true);

    expect($manifest['name'])->toBe('Perla Rodriguez')
        ->and($manifest['short_name'])->toBe('Perla');
});

it('links the manifest and registers the service worker on the home page', function () {
    $this->get('/')
        ->assertSee('<link rel="manifest" href="'.url('/manifest.webmanifest').'">', false)
        ->assertSee("navigator.serviceWorker.register('/sw.js')", false)
        ->assertSee('icons/apple-touch-icon.png');
});

it('ships the icons, service worker and offline page', function () {
    foreach (['sw.js', 'offline.html', 'icons/icon-192.png', 'icons/icon-512.png', 'icons/icon-maskable-512.png', 'icons/apple-touch-icon.png'] as $file) {
        expect(public_path($file))->toBeFile();
    }

    [$width, $height] = getimagesize(public_path('icons/icon-512.png'));

    expect([$width, $height])->toBe([512, 512]);
});
