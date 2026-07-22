<?php

namespace Database\Factories;

use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Route>
 */
class RouteFactory extends Factory
{
    protected $model = Route::class;

    public function definition(): array
    {
        return [
            'name' => 'RN'.fake()->unique()->numberBetween(60, 999),
            'departure_city' => fake()->city(),
            'arrival_city' => fake()->city(),
            'distance_km' => fake()->numberBetween(20, 900),
            'estimated_duration_minutes' => fake()->numberBetween(30, 900),
            'description' => null,
        ];
    }
}
