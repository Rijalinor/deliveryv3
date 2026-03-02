<?php

namespace Tests\Unit;

use App\Services\Geocoding\OrsGeocodingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrsGeocodingServiceTest extends TestCase
{
    public function test_search_returns_filtered_items_from_ors(): void
    {
        config(['services.ors.key' => 'test-key']);

        Http::fake([
            'https://api.openrouteservice.org/geocode/search*' => Http::response([
                'features' => [
                    [
                        'properties' => ['label' => 'Result ORS 1'],
                        'geometry' => ['coordinates' => [106.8, -6.2]],
                    ],
                    [
                        'properties' => ['label' => 'Result ORS 2'],
                        'geometry' => ['coordinates' => [106.9, -6.3]],
                    ],
                ],
            ], 200),
        ]);

        $result = (new OrsGeocodingService)->search('jakarta');

        $this->assertTrue($result['ok']);
        $this->assertCount(2, $result['data']);
        $this->assertSame('Result ORS 1', $result['data'][0]['label']);
        $this->assertSame('ors', $result['data'][0]['source']);
    }

    public function test_search_falls_back_to_photon_on_ors_failure(): void
    {
        config(['services.ors.key' => 'test-key']);

        Http::fake([
            'https://api.openrouteservice.org/geocode/search*' => Http::response([], 500),
            'https://photon.komoot.io/api/*' => Http::response([
                'features' => [
                    [
                        'properties' => ['name' => 'Photon Store', 'street' => 'Jl. Test'],
                        'geometry' => ['coordinates' => [114.5, -3.3]],
                    ],
                ],
            ], 200),
        ]);

        $result = (new OrsGeocodingService)->search('toko');

        $this->assertTrue($result['ok']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('Photon Store, Jl. Test', $result['data'][0]['label']);
        $this->assertSame('photon', $result['data'][0]['source']);
    }

    public function test_reverse_returns_label(): void
    {
        config(['services.ors.key' => 'test-key']);

        Http::fake([
            'https://api.openrouteservice.org/geocode/reverse*' => Http::response([
                'features' => [
                    [
                        'properties' => ['label' => 'Jalan Test 123'],
                    ],
                ],
            ], 200),
        ]);

        $result = (new OrsGeocodingService)->reverse(-6.2, 106.8);

        $this->assertTrue($result['ok']);
        $this->assertSame('Jalan Test 123', $result['data']['label']);
    }

    public function test_reverse_returns_error_on_failure(): void
    {
        config(['services.ors.key' => 'test-key']);

        Http::fake([
            'https://api.openrouteservice.org/geocode/reverse*' => Http::response([], 500),
        ]);

        $result = (new OrsGeocodingService)->reverse(-6.2, 106.8);

        $this->assertFalse($result['ok']);
        $this->assertSame('ORS reverse failed', $result['message']);
    }
}
