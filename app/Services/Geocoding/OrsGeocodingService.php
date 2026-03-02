<?php

namespace App\Services\Geocoding;

use Illuminate\Support\Facades\Http;

class OrsGeocodingService
{
    private string $baseUrl = 'https://api.openrouteservice.org/geocode';

    public function search(string $text, int $size = 5): array
    {
        $apiKey = config('services.ors.key');

        $resp = Http::timeout(15)
            ->get($this->baseUrl.'/search', [
                'api_key' => $apiKey,
                'text' => $text,
                'size' => $size,
                'boundary.country' => 'ID',
            ]);

        $items = [];
        if ($resp->successful()) {
            $json = $resp->json();
            $items = collect($json['features'] ?? [])->map(function ($f) {
                $props = $f['properties'] ?? [];
                $geom = $f['geometry']['coordinates'] ?? [null, null]; // [lon, lat]

                return [
                    'label' => $props['label'] ?? ($props['name'] ?? 'Unknown'),
                    'lat' => isset($geom[1]) ? (float) $geom[1] : null,
                    'lng' => isset($geom[0]) ? (float) $geom[0] : null,
                    'source' => 'ors',
                ];
            })->filter(fn ($i) => $i['lat'] && $i['lng'])->values()->all();
        }

        // Kalau ORS hasilnya sedikit (kurang dari 2), coba pakai Photon (Komoot)
        // Photon biasanya lebih bagus untuk POI (nama toko/gedung)
        if (count($items) < 2) {
            $photonItems = $this->searchPhoton($text, $size);
            $items = array_merge($items, $photonItems);
        }

        return ['ok' => true, 'message' => null, 'data' => collect($items)->unique('label')->values()->all()];
    }

    public function searchPhoton(string $text, int $size = 5): array
    {
        try {
            $resp = Http::timeout(10)
                ->get('https://photon.komoot.io/api/', [
                    'q' => $text,
                    'limit' => $size,
                    'lat' => -3.3190, // Bias ke Banjarmasin/Kalsel jika data minim
                    'lon' => 114.5900,
                ]);

            if (! $resp->successful()) {
                return [];
            }

            $json = $resp->json();

            return collect($json['features'] ?? [])->map(function ($f) {
                $p = $f['properties'] ?? [];
                $g = $f['geometry']['coordinates'] ?? [null, null];

                $label = collect([
                    $p['name'] ?? null,
                    $p['street'] ?? null,
                    $p['city'] ?? $p['county'] ?? null,
                ])->filter()->join(', ');

                return [
                    'label' => $label ?: 'Unknown',
                    'lat' => isset($g[1]) ? (float) $g[1] : null,
                    'lng' => isset($g[0]) ? (float) $g[0] : null,
                    'source' => 'photon',
                ];
            })->filter(fn ($i) => $i['lat'] && $i['lng'])->values()->all();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function reverse(float $lat, float $lng): array
    {
        $apiKey = config('services.ors.key');

        $resp = Http::timeout(15)
            ->get($this->baseUrl.'/reverse', [
                'api_key' => $apiKey,
                'point.lat' => $lat,
                'point.lon' => $lng,
                'size' => 1,
            ]);

        if (! $resp->successful()) {
            return ['ok' => false, 'message' => 'ORS reverse failed', 'data' => null];
        }

        $json = $resp->json();
        $feature = $json['features'][0] ?? null;

        if (! $feature) {
            return ['ok' => false, 'message' => 'No features found', 'data' => null];
        }

        $props = $feature['properties'] ?? [];
        $geom = $feature['geometry']['coordinates'] ?? [null, null];

        return [
            'ok' => true,
            'message' => null,
            'data' => [
                'label' => $props['label'] ?? null,
                'lat' => isset($geom[1]) ? (float) $geom[1] : null,
                'lng' => isset($geom[0]) ? (float) $geom[0] : null,
            ],
        ];
    }
}
