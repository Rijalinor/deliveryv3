<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OrsService
{

    public function optimize(array $start, array $jobs, int $startTimeSec = 0): array
    {
        $key = config('services.ors.key');
        if (! $key) {
            throw new \RuntimeException('ORS_API_KEY kosong. Cek .env dan config/services.php');
        }

        $url = 'https://api.openrouteservice.org/optimization';

        $payload = [
            'vehicles' => [[
                'id' => 1,
                'profile' => 'driving-car',
                'start' => $start,            // [lng, lat]
                'end' => $start,              // optional: balik gudang
                'time_window' => [$startTimeSec, 24 * 3600 - 1], // boleh jalan sampai akhir hari
            ]],
            'jobs' => $jobs,
        ];

        $res = Http::timeout(60)
            ->withHeaders([
                'Authorization' => $key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($url, $payload);

        if (! $res->successful()) {
            $body = $res->json();
            $msg  = data_get($body, 'error.message') ?? $res->body();
            $code = data_get($body, 'error.code') ?? $res->status();
            throw new \RuntimeException("ORS error ({$code}): {$msg}");
        }

        return $res->json();
    }
    
    public function matrix(array $locations): array
    {
        $key = config('services.ors.key');

        if (! $key) {
            throw new \RuntimeException('ORS_API_KEY kosong. Cek .env dan config/services.php');
        }

        $profile = config('ors.profile', 'driving-car');
        $url = "https://api.openrouteservice.org/v2/matrix/{$profile}";

        $payload = [
            'locations' => $locations,          // [[lng, lat], ...]
            'metrics' => ['duration'],          // seconds
        ];

        $res = Http::timeout(30)
            ->withHeaders([
                'Authorization' => $key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($url, $payload);

        if (! $res->successful()) {
            $body = $res->json();
            $msg  = data_get($body, 'error.message') ?? $res->body();
            $code = data_get($body, 'error.code') ?? $res->status();

            throw new \RuntimeException("ORS matrix error ({$code}): {$msg}");
        }

        return $res->json();
    }

    public function directions(array $coordinates, string $profile = 'driving-car'): array
    {
        $key = config('services.ors.key');

        if (! $key) {
            throw new \RuntimeException('ORS_API_KEY kosong. Cek .env dan config/services.php');
        }

        $url = "https://api.openrouteservice.org/v2/directions/{$profile}/geojson";

        $payload = [
            'coordinates' => $coordinates,
            'instructions' => false,
        ];

        $res = Http::timeout(30)
            ->withHeaders([
                'Authorization' => $key,
                // ✅ ini kuncinya untuk endpoint geojson
                'Accept' => 'application/geo+json',
                'Content-Type' => 'application/json',
            ])
            ->post($url, $payload);

        if (! $res->successful()) {
            $body = $res->json();
            $msg  = data_get($body, 'error.message') ?? $res->body();
            $code = data_get($body, 'error.code') ?? $res->status();
            throw new \RuntimeException("ORS error ({$code}): {$msg}");
        }

        return $res->json();
    }
}
