<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver_id' => User::factory(),
            'gi_number' => $this->faker->unique()->bothify('GI-#####'),
            'start_date' => now()->format('Y-m-d'),
            'start_time' => '08:00',
            'start_address' => 'Warehouse Central',
            'start_lat' => -3.356837,
            'start_lng' => 114.577059,
            'status' => 'planned',
            'ors_profile' => 'driving-car',
        ];
    }
}
