<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Geocoding\OrsGeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeocodingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_empty_for_short_query(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/admin/api/geocode?q=ab')
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'data' => [],
            ]);
    }

    public function test_search_returns_service_payload(): void
    {
        $user = User::factory()->create();

        $this->mock(OrsGeocodingService::class, function ($mock) {
            $mock->shouldReceive('search')
                ->once()
                ->with('jakarta')
                ->andReturn([
                    'ok' => true,
                    'message' => null,
                    'data' => [['label' => 'Jakarta', 'lat' => -6.2, 'lng' => 106.8]],
                ]);
        });

        $this->actingAs($user)
            ->getJson('/admin/api/geocode?q=jakarta')
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'data' => [['label' => 'Jakarta', 'lat' => -6.2, 'lng' => 106.8]],
            ]);
    }

    public function test_reverse_requires_lat_lng(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/admin/api/reverse')
            ->assertOk()
            ->assertJson([
                'ok' => false,
                'message' => 'lat/lng required',
            ]);
    }

    public function test_reverse_returns_service_payload(): void
    {
        $user = User::factory()->create();

        $this->mock(OrsGeocodingService::class, function ($mock) {
            $mock->shouldReceive('reverse')
                ->once()
                ->with(-6.2, 106.8)
                ->andReturn([
                    'ok' => true,
                    'message' => null,
                    'data' => ['label' => 'Jalan Test 123'],
                ]);
        });

        $this->actingAs($user)
            ->getJson('/admin/api/reverse?lat=-6.2&lng=106.8')
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'data' => ['label' => 'Jalan Test 123'],
            ]);
    }
}
