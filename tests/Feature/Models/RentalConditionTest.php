<?php

namespace Tests\Feature\Models;

use App\Enums\RentalZone;
use App\Models\RentalCondition;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalConditionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_condition_belongs_to_a_vehicle_which_exposes_it_back(): void
    {
        $vehicle = Vehicle::factory()->create();
        $condition = RentalCondition::create(['vehicle_id' => $vehicle->id]);

        $rate = $condition->rentalRates()->create([
            'zone' => RentalZone::Suburb,
            'min_days' => 1,
            'max_days' => 5,
            'daily_rate' => 220000,
        ]);

        $this->assertTrue($condition->vehicle->is($vehicle));
        $this->assertTrue($vehicle->fresh()->rentalCondition->is($condition));
        $this->assertTrue($condition->rentalRates->contains($rate));
        $this->assertSame(RentalZone::Suburb, $rate->fresh()->zone);
        $this->assertSame('220000.00', $rate->fresh()->daily_rate);
    }

    public function test_an_unsaved_condition_already_carries_the_default_thresholds(): void
    {
        $condition = new RentalCondition;

        $this->assertSame(50, $condition->city_max_km);
        $this->assertSame(100, $condition->suburb_max_km);
        $this->assertSame(700, $condition->long_distance_max_km);
    }

    public function test_the_open_zone_reports_no_upper_bound(): void
    {
        $condition = new RentalCondition;

        $this->assertSame(50, $condition->maxKmFor(RentalZone::City));
        $this->assertSame(100, $condition->maxKmFor(RentalZone::Suburb));
        $this->assertSame(700, $condition->maxKmFor(RentalZone::LongDistance));
        $this->assertNull($condition->maxKmFor(RentalZone::VeryLongDistance));
    }
}
