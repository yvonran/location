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
            'city_max_km' => RentalCondition::DEFAULT_CITY_MAX_KM,
            'suburb_max_km' => RentalCondition::DEFAULT_SUBURB_MAX_KM,
            'long_distance_max_km' => RentalCondition::DEFAULT_LONG_DISTANCE_MAX_KM,
        ];
    }
}
