<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_trip()
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/trips', [
            'driver_id' => User::factory()->create()->id,
            'start_date' => now(),
            'start_time' => '08:00',
        ]);

        $this->assertDatabaseHas('trips', [
            'driver_id' => $admin->id,
        ]);
    }

    public function test_trip_can_have_multiple_stops()
    {
        $trip = Trip::factory()->create();

        $trip->stops()->createMany([
            ['store_id' => 1, 'sequence' => 1],
            ['store_id' => 2, 'sequence' => 2],
            ['store_id' => 3, 'sequence' => 3],
        ]);

        $this->assertEquals(3, $trip->stops()->count());
    }
}
