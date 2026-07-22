<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '03'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->city(),
            'tax_id' => fake()->optional()->numerify('NIF#######'),
        ];
    }
}
