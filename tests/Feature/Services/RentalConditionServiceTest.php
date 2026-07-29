<?php

namespace Tests\Feature\Services;

use App\Enums\RentalZone;
use App\Models\RentalCondition;
use App\Models\Vehicle;
use App\Services\RentalConditionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RentalConditionServiceTest extends TestCase
{
    use RefreshDatabase;

    private RentalConditionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RentalConditionService::class);
    }

    private function vehicleWithRates(array $thresholds = []): Vehicle
    {
        $vehicle = Vehicle::factory()->create();

        $condition = RentalCondition::create([
            'vehicle_id' => $vehicle->id,
            ...$thresholds,
        ]);

        foreach ([
            [RentalZone::City, 1, 5, 180000],
            [RentalZone::City, 6, null, 160000],
            [RentalZone::Suburb, 1, null, 220000],
            [RentalZone::LongDistance, 1, null, 250000],
            [RentalZone::VeryLongDistance, 1, null, 350000],
        ] as [$zone, $minDays, $maxDays, $dailyRate]) {
            $condition->rentalRates()->create([
                'zone' => $zone, 'min_days' => $minDays,
                'max_days' => $maxDays, 'daily_rate' => $dailyRate,
            ]);
        }

        return $vehicle->fresh();
    }

    public static function zoneCases(): array
    {
        return [
            'en deçà de la limite ville' => [10.0, RentalZone::City],
            'pile sur la limite ville' => [50.0, RentalZone::City],
            'juste au-dessus de la limite ville' => [50.5, RentalZone::Suburb],
            'pile sur la limite périphérie' => [100.0, RentalZone::Suburb],
            'au-dessus de la périphérie' => [101.0, RentalZone::LongDistance],
            'pile sur la limite longue distance' => [700.0, RentalZone::LongDistance],
            'au-delà de 700 km' => [700.1, RentalZone::VeryLongDistance],
            'très loin' => [1200.0, RentalZone::VeryLongDistance],
        ];
    }

    #[DataProvider('zoneCases')]
    public function test_the_zone_is_resolved_from_the_one_way_distance(float $oneWayKm, RentalZone $expected): void
    {
        $vehicle = $this->vehicleWithRates();

        $this->assertSame($expected, $this->service->zoneFor($vehicle, $oneWayKm));
    }

    public function test_the_thresholds_are_configurable_per_vehicle(): void
    {
        $vehicle = $this->vehicleWithRates([
            'city_max_km' => 20,
            'suburb_max_km' => 60,
            'long_distance_max_km' => 400,
        ]);

        $this->assertSame(RentalZone::City, $this->service->zoneFor($vehicle, 20.0));
        $this->assertSame(RentalZone::Suburb, $this->service->zoneFor($vehicle, 45.0));
        $this->assertSame(RentalZone::LongDistance, $this->service->zoneFor($vehicle, 399.0));
        $this->assertSame(RentalZone::VeryLongDistance, $this->service->zoneFor($vehicle, 401.0));
    }

    public function test_the_rate_matches_both_the_zone_and_the_day_range(): void
    {
        $vehicle = $this->vehicleWithRates();

        $short = $this->service->findRate($vehicle, 30.0, 3);
        $long = $this->service->findRate($vehicle, 30.0, 9);

        $this->assertSame('180000.00', $short?->daily_rate);
        $this->assertSame('160000.00', $long?->daily_rate);
    }

    public function test_an_open_ended_day_range_matches_any_long_duration(): void
    {
        $vehicle = $this->vehicleWithRates();

        $rate = $this->service->findRate($vehicle, 900.0, 45);

        $this->assertSame(RentalZone::VeryLongDistance, $rate?->zone);
        $this->assertSame('350000.00', $rate?->daily_rate);
    }

    public function test_no_rate_is_returned_when_no_day_range_matches(): void
    {
        $vehicle = Vehicle::factory()->create();
        $condition = RentalCondition::create(['vehicle_id' => $vehicle->id]);
        $condition->rentalRates()->create([
            'zone' => RentalZone::City, 'min_days' => 5, 'max_days' => 10, 'daily_rate' => 180000,
        ]);

        $this->assertNull($this->service->findRate($vehicle->fresh(), 10.0, 2));
    }

    public function test_a_vehicle_without_conditions_has_no_zone_and_no_rate(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->assertNull($this->service->zoneFor($vehicle, 30.0));
        $this->assertNull($this->service->findRate($vehicle, 30.0, 2));
    }
}
