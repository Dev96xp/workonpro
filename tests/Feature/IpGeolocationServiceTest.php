<?php

use App\Services\IpGeolocationService;
use Illuminate\Support\Facades\Http;

it('returns the city for a public ip when the lookup succeeds', function () {
    config(['services.ipinfo.token' => 'fake-token']);

    Http::fake([
        'ipinfo.io/*' => Http::response(['city' => 'Omaha'], 200),
    ]);

    expect(app(IpGeolocationService::class)->city('8.8.8.8'))->toBe('Omaha');
});

it('returns null for a private or loopback ip without calling the api', function () {
    config(['services.ipinfo.token' => 'fake-token']);

    Http::fake();

    expect(app(IpGeolocationService::class)->city('127.0.0.1'))->toBeNull();

    Http::assertNothingSent();
});

it('returns null when no ipinfo token is configured', function () {
    config(['services.ipinfo.token' => null]);

    Http::fake();

    expect(app(IpGeolocationService::class)->city('8.8.8.8'))->toBeNull();

    Http::assertNothingSent();
});

it('returns null when the lookup fails', function () {
    config(['services.ipinfo.token' => 'fake-token']);

    Http::fake([
        'ipinfo.io/*' => Http::response([], 500),
    ]);

    expect(app(IpGeolocationService::class)->city('8.8.8.8'))->toBeNull();
});
