<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class IpGeolocationService
{
    public function city(string $ip): ?string
    {
        return $this->location($ip)['city'] ?? null;
    }

    /**
     * @return array{city: ?string, state: ?string, country: ?string}|null
     */
    public function location(string $ip): ?array
    {
        $token = config('services.ipinfo.token');

        if (! $token || ! $this->isPublicIp($ip)) {
            return null;
        }

        try {
            $response = Http::timeout(3)->get("https://ipinfo.io/{$ip}", [
                'token' => $token,
            ]);

            if (! $response->successful()) {
                return null;
            }

            return [
                'city' => $response->json('city'),
                'state' => $response->json('region'),
                'country' => $response->json('country'),
            ];
        } catch (Throwable $e) {
            Log::warning('IP geolocation lookup failed', ['ip' => $ip, 'message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * The visitor's real address: behind Cloudflare request()->ip() is the proxy, not the visitor.
     */
    public static function clientIp(): string
    {
        return (string) (request()->header('CF-Connecting-IP') ?: request()->ip());
    }

    private function isPublicIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
