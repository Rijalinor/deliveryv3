<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Store',
            'address' => $this->faker->address(),
            'lat' => -3.3 + ($this->faker->randomFloat(6, 0, 0.1)),
            'lng' => 114.5 + ($this->faker->randomFloat(6, 0, 0.1)),
            'close_time' => '17:00',
        ];
    }
}
