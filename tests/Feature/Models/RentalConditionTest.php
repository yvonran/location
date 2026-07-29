<?php

namespace Tests\Feature\Models;

use App\Models\RentalCondition;
use App\Models\RentalRate;
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

        $zone = $condition->rentalZones()->create([
            'name' => 'Périphérie',
            'max_km' => 100,
            'position' => 0,
        ]);

        $rate = $zone->rentalRates()->create([
            'min_days' => 1,
            'max_days' => 5,
            'daily_rate' => 220000,
        ]);

        $this->assertTrue($condition->vehicle->is($vehicle));
        $this->assertTrue($vehicle->fresh()->rentalCondition->is($condition));
        $this->assertTrue($condition->rentalZones->contains($zone));
        $this->assertTrue($zone->rentalRates->contains($rate));
        $this->assertSame('220000.00', $rate->fresh()->daily_rate);
    }

    public function test_a_zone_without_an_upper_bound_covers_any_distance(): void
    {
        $vehicle = Vehicle::factory()->create();
        $condition = RentalCondition::create(['vehicle_id' => $vehicle->id]);

        $closed = $condition->rentalZones()->create(['name' => 'Ville', 'max_km' => 50, 'position' => 0]);
        $open = $condition->rentalZones()->create(['name' => 'Reste', 'max_km' => null, 'position' => 1]);

        $this->assertFalse($closed->isOpenEnded());
        $this->assertTrue($closed->covers(50.0));
        $this->assertFalse($closed->covers(51.0));

        $this->assertTrue($open->isOpenEnded());
        $this->assertTrue($open->covers(9999.0));
    }

    public function test_the_factories_build_a_usable_set_of_conditions(): void
    {
        $condition = RentalCondition::factory()->withDefaultZones()->create();

        $this->assertCount(4, $condition->rentalZones);
        $this->assertSame('Ville', $condition->rentalZones->firstWhere('position', 0)->name);
        $this->assertNull($condition->rentalZones->firstWhere('position', 3)->max_km);

        $rate = RentalRate::factory()->create();

        $this->assertNotNull($rate->rentalZone);
        $this->assertTrue($rate->rentalZone->rentalRates->contains($rate));
    }

    public function test_the_default_zones_offer_the_usual_four_bands(): void
    {
        $this->assertSame(
            [['Ville', 50], ['Périphérie', 100], ['Longue distance', 700], ['Très longue distance', null]],
            RentalCondition::DEFAULT_ZONES,
        );
    }
}
