<?php

namespace Database\Factories;

use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceType>
 */
class ServiceTypeFactory extends Factory
{
    protected $model = ServiceType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'coefficient' => fake()->randomFloat(2, 1, 2),
            'description' => null,
            'active' => true,
        ];
    }
}
