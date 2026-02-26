<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrsService
{
    private function http(string $key, int $timeout = 60)
    {
        return Http::retry(2, 1200)      // retry kalau koneksi ngadat
            ->connectTimeout(20)         // penting untuk kasus "Resolving timed out"
            ->timeout($timeout)
            ->withHeaders([
                'Authorization' => $key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
    }

    public function optimize(array $start, array $jobs, int $startTimeSec = 0): array
    {
        $key = config('services.ors.key');
        if (! $key) {
            throw new \RuntimeException('ORS_API_KEY kosong. Cek .env dan config/services.php');
        }

        $url = 'https://api.openrouteservice.org/optimization';

        $profile = config('delivery.ors_profile', 'driving-car');

        $payload = [
            'vehicles' => [[
                'id' => 1,
                'profile' => $profile,
                'start' => $start,
                'end' => $start,
                'time_window' => [$startTimeSec, 24 * 3600 - 1],
            ]],
            'jobs' => $jobs,
        ];

        try {
            $res = $this->http($key, 60)->post($url, $payload);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $res = $e->response;
        }

        if (! $res->successful()) {
            $body = $res->json();
            $msg = data_get($body, 'error.message') ?? $res->body();
            $code = data_get($body, 'error.code') ?? $res->status();

            Log::error('ORS optimize() failed', [
                'code' => $code,
                'message' => $msg,
                'profile' => $profile,
                'jobs' => count($jobs),
            ]);

            $hint = match (true) {
                $res->status() === 429 => ' (Rate limit ORS tercapai — coba lagi dalam 1 menit)',
                $res->status() === 403 => ' (API Key tidak valid — cek ORS_API_KEY di .env)',
                default => '',
            };

            throw new \RuntimeException("ORS optimize error ({$code}): {$msg}{$hint}");
        }

        return $res->json();
    }

    public function matrix(array $locations): array
    {
        $key = config('services.ors.key');
        if (! $key) {
            throw new \RuntimeException('ORS_API_KEY kosong. Cek .env dan config/services.php');
        }

        $profile = config('delivery.ors_profile', 'driving-car');
        $url = "https://api.openrouteservice.org/v2/matrix/{$profile}";

        $payload = [
            'locations' => $locations,
            'metrics' => ['duration', 'distance'],
        ];

        try {
            $res = $this->http($key, 30)->post($url, $payload);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $res = $e->response;
        }

        if (! $res->successful()) {
            $body = $res->json();
            $msg = data_get($body, 'error.message') ?? $res->body();
            $code = data_get($body, 'error.code') ?? $res->status();

            Log::error('ORS matrix() failed', [
                'code' => $code,
                'message' => $msg,
                'profile' => $profile,
                'locations' => count($locations),
            ]);

            $hint = match (true) {
                $res->status() === 429 => ' (Rate limit ORS — coba lagi sebentar)',
                str_contains(strtolower($msg), 'point') => ' (Salah satu koordinat toko tidak bisa dijangkau di jalan — cek koordinat toko)',
                default => '',
            };

            throw new \RuntimeException("ORS matrix error ({$code}): {$msg}{$hint}");
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
            'radiuses' => array_fill(0, count($coordinates), 500),
        ];

        try {
            $res = Http::retry(2, 1200)
                ->connectTimeout(20)
                ->timeout(30)
                ->withHeaders([
                    'Authorization' => $key,
                    'Accept' => 'application/geo+json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $res = $e->response;
        }

        if (! $res->successful()) {
            $body = $res->json();
            $msg = data_get($body, 'error.message') ?? $res->body();
            $code = data_get($body, 'error.code') ?? $res->status();

            Log::error('ORS directions() failed', [
                'code' => $code,
                'message' => $msg,
                'profile' => $profile,
                'coordinates' => count($coordinates),
            ]);

            throw new \RuntimeException("ORS directions error ({$code}): {$msg}");
        }

        return $res->json();
    }
}
