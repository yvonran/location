<?php

namespace Database\Factories;

use App\Models\RentalRate;
use App\Models\RentalZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalRate>
 */
class RentalRateFactory extends Factory
{
    protected $model = RentalRate::class;

    public function definition(): array
    {
        return [
            'rental_zone_id' => RentalZone::factory(),
            'min_days' => 1,
            'max_days' => null,
            'daily_rate' => fake()->numberBetween(150, 500) * 1000,
        ];
    }
}
