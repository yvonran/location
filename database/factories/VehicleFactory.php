<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'vehicle_model_id' => VehicleModel::factory(),
            'seats' => fake()->numberBetween(4, 30),
            'registration_number' => fake()->unique()->numerify('####').' '.fake()->randomElement(['TBA', 'TBT', 'TBM']),
            'year' => fake()->numberBetween(2015, 2026),
            'has_air_conditioning' => fake()->boolean(80),
            'average_consumption' => fake()->randomFloat(2, 5, 20),
            'status' => VehicleStatus::Available,
        ];
    }
}
