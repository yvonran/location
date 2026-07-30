<?php

namespace Database\Factories;

use App\Models\Simulation;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Simulation>
 */
class SimulationFactory extends Factory
{
    protected $model = Simulation::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'number_of_days' => fake()->numberBetween(1, 10),
            'departure_time' => null,
            'distance_km' => fake()->randomFloat(2, 10, 900),
            'daily_rate' => fake()->randomFloat(2, 150000, 400000),
            'meal_charged_to_client' => true,
            'fuel_charged_to_client' => true,
            'meal_cost' => 0,
            'fuel_cost' => 0,
            'vehicle_amount' => 0,
            'total' => 0,
        ];
    }
}
