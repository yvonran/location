<?php

namespace Database\Factories;

use App\Enums\AmountMode;
use App\Models\OptionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OptionType>
 */
class OptionTypeFactory extends Factory
{
    protected $model = OptionType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'default_mode' => fake()->randomElement(AmountMode::cases()),
            'default_value' => fake()->randomFloat(2, 5000, 100000),
            'active' => true,
        ];
    }
}
