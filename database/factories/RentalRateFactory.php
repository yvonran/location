<?php

namespace Database\Factories;

use App\Enums\RentalZone;
use App\Models\RentalCondition;
use App\Models\RentalRate;
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
            'rental_condition_id' => RentalCondition::factory(),
            'zone' => fake()->randomElement(RentalZone::cases()),
            'min_days' => 1,
            'max_days' => null,
            'daily_rate' => fake()->numberBetween(150, 500) * 1000,
        ];
    }

    public function zone(RentalZone $zone): static
    {
        return $this->state(fn () => ['zone' => $zone]);
    }
}
