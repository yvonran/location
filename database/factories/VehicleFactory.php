<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
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
            'brand' => fake()->randomElement(['Toyota', 'Hyundai', 'Nissan', 'Mitsubishi']),
            'model' => fake()->word(),
            'seats' => fake()->numberBetween(4, 30),
            'registration_number' => fake()->unique()->numerify('####').' '.fake()->randomElement(['TBA', 'TBT', 'TBM']),
            'year' => fake()->numberBetween(2015, 2026),
            'has_air_conditioning' => fake()->boolean(80),
            'status' => VehicleStatus::Available,
        ];
    }
}
