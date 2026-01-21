<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_requires_driver(): void
    {
        $this->expectException(QueryException::class);

        Trip::create([
            'start_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'start_lat' => 0.1,
            'start_lng' => 0.2,
            'status' => 'planned',
        ]);
    }

    public function test_trip_requires_start_date_and_time(): void
    {
        $driver = User::factory()->create();

        $this->expectException(QueryException::class);

        Trip::create([
            'driver_id' => $driver->id,
            'start_lat' => 0.1,
            'start_lng' => 0.2,
            'status' => 'planned',
        ]);
    }

    public function test_trip_requires_start_coordinates(): void
    {
        $driver = User::factory()->create();

        $this->expectException(QueryException::class);

        Trip::create([
            'driver_id' => $driver->id,
            'start_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'status' => 'planned',
        ]);
    }
}
