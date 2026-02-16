<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TripStop>
 */
class TripStopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'store_id' => Store::factory(),
            'status' => 'pending',
            'sequence' => null,
            'eta_at' => null,
            'close_at' => null,
        ];
    }
}
