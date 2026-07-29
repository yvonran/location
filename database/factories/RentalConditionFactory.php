<?php

namespace Database\Factories;

use App\Models\RentalCondition;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalCondition>
 */
class RentalConditionFactory extends Factory
{
    protected $model = RentalCondition::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
        ];
    }

    /**
     * Conditions garnies du découpage proposé par défaut.
     */
    public function withDefaultZones(): static
    {
        return $this->afterCreating(function (RentalCondition $condition) {
            foreach (RentalCondition::DEFAULT_ZONES as $position => [$name, $maxKm]) {
                $condition->rentalZones()->create([
                    'name' => $name,
                    'max_km' => $maxKm,
                    'position' => $position,
                ]);
            }
        });
    }
}
