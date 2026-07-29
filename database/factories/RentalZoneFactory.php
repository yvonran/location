<?php

namespace Database\Factories;

use App\Models\RentalCondition;
use App\Models\RentalZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalZone>
 */
class RentalZoneFactory extends Factory
{
    protected $model = RentalZone::class;

    public function definition(): array
    {
        return [
            'rental_condition_id' => RentalCondition::factory(),
            'name' => fake()->randomElement(['Ville', 'Périphérie', 'Longue distance']),
            'max_km' => fake()->numberBetween(50, 700),
            'position' => 0,
        ];
    }

    public function openEnded(): static
    {
        return $this->state(fn () => ['max_km' => null]);
    }
}
