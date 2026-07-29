<?php

namespace Tests\Feature\Services;

use App\Exceptions\NoTariffFoundException;
use App\Models\SimulationSetting;
use App\Models\Tariff;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Services\SimulationCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function vehicle(): Vehicle
    {
        return Vehicle::create([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true, 'average_consumption' => 10,
        ]);
    }

    public function test_it_calculates_fuel_and_meal_costs_when_neither_is_covered(): void
    {
        $vehicle = $this->vehicle();
        Tariff::create([
            'vehicle_id' => $vehicle->id, 'min_distance_km' => 0, 'max_distance_km' => 799,
            'min_days' => 1, 'max_days' => 5, 'daily_rate' => 250000,
        ]);
        SimulationSetting::current()->update(['fuel_price_per_liter' => 5000, 'client_meal_price' => 7000]);

        $service = app(SimulationCalculationService::class);

        $simulation = $service->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'meal_included' => false,
            'fuel_included' => false,
            'legs' => [
                ['from_point' => 'Antananarivo', 'to_point' => 'Moramanga', 'distance_km' => 120],
                ['from_point' => 'Moramanga', 'to_point' => 'Toamasina', 'distance_km' => 330],
            ],
        ]);

        // distance = 450 km ; fuel = 10/100 x 450 x 5000 = 225,000
        $this->assertSame('450.00', (string) $simulation->distance_km);
        $this->assertSame('225000.00', (string) $simulation->fuel_cost);
        // 3 days, no departure time => 2 meals/day x 3 = 6 meals x 7000 = 42,000
        $this->assertSame('42000.00', (string) $simulation->meal_cost);
        // vehicle amount = 250000 x 3 = 750,000
        $this->assertSame('750000.00', (string) $simulation->vehicle_amount);
        $this->assertSame('1017000.00', (string) $simulation->total);
    }

    public function test_it_skips_fuel_and_meal_costs_when_both_are_covered(): void
    {
        $vehicle = $this->vehicle();
        Tariff::create([
            'vehicle_id' => $vehicle->id, 'min_distance_km' => 0, 'max_distance_km' => 799,
            'min_days' => 1, 'max_days' => 5, 'daily_rate' => 250000,
        ]);

        $service = app(SimulationCalculationService::class);

        $simulation = $service->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'meal_included' => true,
            'fuel_included' => true,
            'legs' => [
                ['from_point' => 'Antananarivo', 'to_point' => 'Toamasina', 'distance_km' => 450],
            ],
        ]);

        $this->assertSame('0.00', (string) $simulation->fuel_cost);
        $this->assertSame('0.00', (string) $simulation->meal_cost);
        $this->assertSame('750000.00', (string) $simulation->total);
    }

    public function test_a_departure_time_before_noon_counts_three_meals_per_day(): void
    {
        $vehicle = $this->vehicle();
        Tariff::create([
            'vehicle_id' => $vehicle->id, 'min_distance_km' => 0, 'max_distance_km' => 799,
            'min_days' => 1, 'max_days' => 5, 'daily_rate' => 250000,
        ]);
        SimulationSetting::current()->update(['fuel_price_per_liter' => 5000, 'client_meal_price' => 7000]);

        $service = app(SimulationCalculationService::class);

        $simulation = $service->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'departure_time' => '06:00',
            'meal_included' => false,
            'fuel_included' => true,
            'legs' => [
                ['from_point' => 'Antananarivo', 'to_point' => 'Toamasina', 'distance_km' => 450],
            ],
        ]);

        // 3 days x 3 meals x 7000 = 63,000
        $this->assertSame('63000.00', (string) $simulation->meal_cost);
    }

    public function test_it_persists_the_legs_in_order(): void
    {
        $vehicle = $this->vehicle();
        Tariff::create([
            'vehicle_id' => $vehicle->id, 'min_distance_km' => 0, 'max_distance_km' => 799,
            'min_days' => 1, 'max_days' => 5, 'daily_rate' => 250000,
        ]);

        $service = app(SimulationCalculationService::class);

        $simulation = $service->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'meal_included' => true,
            'fuel_included' => true,
            'legs' => [
                ['from_point' => 'Antananarivo', 'to_point' => 'Moramanga', 'distance_km' => 120],
                ['from_point' => 'Moramanga', 'to_point' => 'Toamasina', 'distance_km' => 200],
                ['from_point' => 'Toamasina', 'to_point' => 'Antananarivo', 'distance_km' => 300],
            ],
        ]);

        $legs = $simulation->legs()->orderBy('position')->get();

        $this->assertCount(3, $legs);
        $this->assertSame(['Antananarivo', 'Moramanga', 'Toamasina'], $legs->pluck('from_point')->all());
        $this->assertSame(0, $legs[0]->position);
        $this->assertSame(2, $legs[2]->position);
    }

    public function test_it_throws_when_no_tariff_matches_the_total_distance(): void
    {
        $vehicle = $this->vehicle();

        $service = app(SimulationCalculationService::class);

        $this->expectException(NoTariffFoundException::class);

        $service->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'meal_included' => true,
            'fuel_included' => true,
            'legs' => [
                ['from_point' => 'Antananarivo', 'to_point' => 'Toamasina', 'distance_km' => 450],
            ],
        ]);
    }
}
