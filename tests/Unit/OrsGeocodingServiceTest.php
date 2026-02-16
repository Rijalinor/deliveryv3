<?php

namespace Tests\Unit;

use App\Services\Geocoding\OrsGeocodingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrsGeocodingServiceTest extends TestCase
{
    public function test_search_returns_filtered_items(): void
    {
        config(['services.ors.key' => 'test-key']);

        Http::fake([
            'https://api.openrouteservice.org/geocode/search*' => Http::response([
                'features' => [
                    [
                        'properties' => ['label' => 'A'],
                        'geometry' => ['coordinates' => [106.8, -6.2]],
                    ],
                    [
                        'properties' => ['label' => 'B'],
                        'geometry' => ['coordinates' => [null, null]],
                    ],
                ],
            ], 200),
        ]);

        $result = (new OrsGeocodingService)->search('jakarta');

        $this->assertTrue($result['ok']);
        $this->assertSame('A', $result['data'][0]['label']);
        $this->assertSame(-6.2, $result['data'][0]['lat']);
        $this->assertSame(106.8, $result['data'][0]['lng']);
    }

    public function test_search_returns_error_on_failure(): void
    {
        config(['services.ors.key' => 'test-key']);

        Http::fake([
            'https://api.openrouteservice.org/geocode/search*' => Http::response([], 500),
        ]);

        $result = (new OrsGeocodingService)->search('bandung');

        $this->assertFalse($result['ok']);
        $this->assertSame('ORS geocode failed', $result['message']);
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
