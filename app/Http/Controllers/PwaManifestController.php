<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PwaManifestController extends Controller
{
    /**
     * Serve the web app manifest for the current host, so the platform installs as
     * Workon and each tenant subdomain installs with its own business name.
     */
    public function __invoke(): JsonResponse
    {
        $name = tenant('id') ? (string) tenant('name') : config('app.name');

        return response()->json([
            'id' => '/',
            'name' => $name,
            'short_name' => mb_strlen($name) <= 12 ? $name : Str::limit(Str::before($name, ' '), 12, ''),
            'description' => __('welcome_v2.hero.subtitle'),
            'lang' => app()->getLocale(),
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#09090b',
            'theme_color' => '#09090b',
            'icons' => [
                ['src' => asset('icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('icons/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }
}
