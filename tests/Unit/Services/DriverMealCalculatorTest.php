<?php

namespace Tests\Unit\Services;

use App\Services\DriverMealCalculator;
use PHPUnit\Framework\TestCase;

class DriverMealCalculatorTest extends TestCase
{
    private DriverMealCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new DriverMealCalculator;
    }

    public function test_a_single_day_rental_counts_one_meal_regardless_of_departure_time(): void
    {
        $this->assertSame(1, $this->calculator->mealCount(1, '06:00'));
        $this->assertSame(1, $this->calculator->mealCount(1, '18:00'));
        $this->assertSame(1, $this->calculator->mealCount(1, null));
    }

    public function test_a_multi_day_rental_counts_three_meals_per_day_when_departure_is_before_noon(): void
    {
        $this->assertSame(9, $this->calculator->mealCount(3, '06:00'));
        $this->assertSame(9, $this->calculator->mealCount(3, '11:59'));
    }

    public function test_a_multi_day_rental_counts_two_meals_per_day_when_departure_is_at_or_after_noon(): void
    {
        $this->assertSame(6, $this->calculator->mealCount(3, '12:00'));
        $this->assertSame(6, $this->calculator->mealCount(3, '18:30'));
    }

    public function test_a_missing_departure_time_defaults_to_two_meals_per_day(): void
    {
        $this->assertSame(6, $this->calculator->mealCount(3, null));
    }

    public function test_meal_cost_multiplies_the_count_by_the_meal_price(): void
    {
        $this->assertSame(63000.0, $this->calculator->mealCost(3, '06:00', 7000));
        $this->assertSame(7000.0, $this->calculator->mealCost(1, '06:00', 7000));
    }
}
