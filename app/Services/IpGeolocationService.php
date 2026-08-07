<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class IpGeolocationService
{
    public function city(string $ip): ?string
    {
        $token = config('services.ipinfo.token');

        if (! $token || ! $this->isPublicIp($ip)) {
            return null;
        }

        try {
            $response = Http::timeout(3)->get("https://ipinfo.io/{$ip}", [
                'token' => $token,
            ]);

            return $response->successful() ? $response->json('city') : null;
        } catch (Throwable $e) {
            Log::warning('IP geolocation lookup failed', ['ip' => $ip, 'message' => $e->getMessage()]);

            return null;
        }
    }

    private function isPublicIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
