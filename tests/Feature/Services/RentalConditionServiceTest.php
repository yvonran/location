<?php

namespace Tests\Feature\Services;

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

    /**
     * @param  array<int, array{0: string, 1: int|null, 2: array<int, array{0: int, 1: int|null, 2: int}>}>  $zones
     */
    private function vehicleWithZones(array $zones): Vehicle
    {
        $vehicle = Vehicle::factory()->create();
        $condition = RentalCondition::create(['vehicle_id' => $vehicle->id]);

        foreach ($zones as $position => [$name, $maxKm, $rates]) {
            $zone = $condition->rentalZones()->create([
                'name' => $name, 'max_km' => $maxKm, 'position' => $position,
            ]);

            foreach ($rates as [$minDays, $maxDays, $dailyRate]) {
                $zone->rentalRates()->create([
                    'min_days' => $minDays, 'max_days' => $maxDays, 'daily_rate' => $dailyRate,
                ]);
            }
        }

        return $vehicle->fresh();
    }

    private function defaultVehicle(): Vehicle
    {
        return $this->vehicleWithZones([
            ['Ville', 50, [[1, 5, 180000], [6, null, 160000]]],
            ['Périphérie', 100, [[1, null, 220000]]],
            ['Longue distance', 700, [[1, null, 250000]]],
            ['Très longue distance', null, [[1, null, 350000]]],
        ]);
    }

    public static function zoneCases(): array
    {
        return [
            'en deçà de la première borne' => [10.0, 'Ville'],
            'pile sur la première borne' => [50.0, 'Ville'],
            'juste au-dessus' => [50.5, 'Périphérie'],
            'pile sur la deuxième borne' => [100.0, 'Périphérie'],
            'au-dessus de la deuxième' => [101.0, 'Longue distance'],
            'pile sur la troisième borne' => [700.0, 'Longue distance'],
            'au-delà de la dernière borne' => [700.1, 'Très longue distance'],
            'très loin' => [1200.0, 'Très longue distance'],
        ];
    }

    #[DataProvider('zoneCases')]
    public function test_the_zone_is_resolved_from_the_one_way_distance(float $oneWayKm, string $expected): void
    {
        $vehicle = $this->defaultVehicle();

        $this->assertSame($expected, $this->service->zoneFor($vehicle, $oneWayKm)?->name);
    }

    public function test_a_vehicle_can_use_its_own_zone_names_and_bounds(): void
    {
        $vehicle = $this->vehicleWithZones([
            ['Antananarivo intra-muros', 20, [[1, null, 150000]]],
            ['Grand Tana', 60, [[1, null, 200000]]],
            ['Reste du pays', null, [[1, null, 400000]]],
        ]);

        $this->assertSame('Antananarivo intra-muros', $this->service->zoneFor($vehicle, 20.0)?->name);
        $this->assertSame('Grand Tana', $this->service->zoneFor($vehicle, 45.0)?->name);
        $this->assertSame('Reste du pays', $this->service->zoneFor($vehicle, 5000.0)?->name);
        $this->assertSame('400000.00', $this->service->findRate($vehicle, 5000.0, 1)?->daily_rate);
    }

    public function test_a_single_open_zone_covers_everything(): void
    {
        $vehicle = $this->vehicleWithZones([
            ['Tarif unique', null, [[1, null, 200000]]],
        ]);

        $this->assertSame('Tarif unique', $this->service->zoneFor($vehicle, 0.0)?->name);
        $this->assertSame('Tarif unique', $this->service->zoneFor($vehicle, 12000.0)?->name);
    }

    public function test_the_rate_matches_both_the_zone_and_the_day_range(): void
    {
        $vehicle = $this->defaultVehicle();

        $this->assertSame('180000.00', $this->service->findRate($vehicle, 30.0, 3)?->daily_rate);
        $this->assertSame('160000.00', $this->service->findRate($vehicle, 30.0, 9)?->daily_rate);
    }

    public function test_no_rate_is_returned_when_no_day_range_matches(): void
    {
        $vehicle = $this->vehicleWithZones([
            ['Ville', null, [[5, 10, 180000]]],
        ]);

        $this->assertNull($this->service->findRate($vehicle, 10.0, 2));
    }

    public function test_a_distance_beyond_a_closed_last_zone_has_no_zone(): void
    {
        $vehicle = $this->vehicleWithZones([
            ['Ville', 50, [[1, null, 180000]]],
        ]);

        $this->assertSame('Ville', $this->service->zoneFor($vehicle, 40.0)?->name);
        $this->assertNull($this->service->zoneFor($vehicle, 60.0));
        $this->assertNull($this->service->findRate($vehicle, 60.0, 2));
    }

    public function test_a_vehicle_without_conditions_has_no_zone_and_no_rate(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->assertNull($this->service->zoneFor($vehicle, 30.0));
        $this->assertNull($this->service->findRate($vehicle, 30.0, 2));
    }

    public function test_a_vehicle_without_conditions_gets_the_default_zones_in_the_editor(): void
    {
        $vehicle = Vehicle::factory()->create();

        $props = $this->service->editorProps($vehicle);

        $this->assertCount(4, $props['zones']);
        $this->assertSame('Ville', $props['zones'][0]['name']);
        $this->assertSame(50, $props['zones'][0]['max_km']);
        $this->assertNull($props['zones'][3]['max_km']);
        $this->assertSame([], $props['zones'][0]['rates']);
    }

    public function test_the_editor_returns_the_saved_zones_in_order(): void
    {
        $vehicle = $this->vehicleWithZones([
            ['Courte', 30, [[1, null, 100000]]],
            ['Moyenne', 200, []],
            ['Longue', null, [[1, 3, 300000], [4, null, 280000]]],
        ]);

        $props = $this->service->editorProps($vehicle);

        $this->assertSame(['Courte', 'Moyenne', 'Longue'], array_column($props['zones'], 'name'));
        $this->assertSame([30, 200, null], array_column($props['zones'], 'max_km'));
        $this->assertCount(2, $props['zones'][2]['rates']);
    }
}
