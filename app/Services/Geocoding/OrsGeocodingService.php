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
            ->get($this->baseUrl . '/search', [
                'api_key' => $apiKey,
                'text' => $text,
                'size' => $size,
                'boundary.country' => 'ID',
            ]);

        if (!$resp->successful()) {
            return ['ok' => false, 'message' => 'ORS geocode failed', 'data' => []];
        }

        $json = $resp->json();

        $items = collect($json['features'] ?? [])->map(function ($f) {
            $props = $f['properties'] ?? [];
            $geom  = $f['geometry']['coordinates'] ?? [null, null]; // [lon, lat]
            return [
                'label' => $props['label'] ?? ($props['name'] ?? 'Unknown'),
                'lat' => isset($geom[1]) ? (float)$geom[1] : null,
                'lng' => isset($geom[0]) ? (float)$geom[0] : null,
                'raw' => $props,
            ];
        })->filter(fn($i) => $i['lat'] && $i['lng'])->values()->all();

        return ['ok' => true, 'message' => null, 'data' => $items];
    }

    public function reverse(float $lat, float $lng): array
    {
        $apiKey = config('services.ors.key');

        $resp = Http::timeout(15)
            ->get($this->baseUrl . '/reverse', [
                'api_key' => $apiKey,
                'point.lat' => $lat,
                'point.lon' => $lng,
                'size' => 1,
            ]);

        if (!$resp->successful()) {
            return ['ok' => false, 'message' => 'ORS reverse failed', 'data' => null];
        }

        $json = $resp->json();
        $feature = $json['features'][0] ?? null;

        $label = $feature['properties']['label'] ?? null;

        return ['ok' => true, 'message' => null, 'data' => ['label' => $label]];
    }
}

