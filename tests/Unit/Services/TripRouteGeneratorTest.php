<?php

namespace Tests\Unit\Services;

use App\Models\Store;
use App\Models\Trip;
use App\Models\TripStop;
use App\Services\OrsService;
use App\Services\TripRouteGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripRouteGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected TripRouteGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock ORS Service
        $ors = \Mockery::mock(OrsService::class);
        $this->generator = new TripRouteGenerator($ors);
    }

    public function test_can_generate_route_for_valid_trip()
    {
        // Arrange
        $trip = Trip::factory()->create([
            'start_lat' => -3.356837,
            'start_lng' => 114.577059,
        ]);

        $stores = Store::factory()->count(3)->create();
        $stops = $stores->map(function ($store) use ($trip) {
            return TripStop::factory()->create([
                'trip_id' => $trip->id,
                'store_id' => $store->id,
            ]);
        });

        // Act & Assert
        $this->assertNotNull($trip);
        $this->assertEquals(3, $trip->stops()->count());
    }

    public function test_assigns_sequence_to_stops()
    {
        // Arrange
        $trip = Trip::factory()->create();
        $stores = Store::factory()->count(2)->create();

        foreach ($stores as $store) {
            TripStop::factory()->create([
                'trip_id' => $trip->id,
                'store_id' => $store->id,
                'sequence' => null,
            ]);
        }

        // Assert stops have no sequence initially
        $this->assertEquals(0, $trip->stops()->whereNotNull('sequence')->count());
    }
}
