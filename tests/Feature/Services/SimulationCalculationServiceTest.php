<?php

namespace Tests\Feature\Services;

use App\Exceptions\NoTariffFoundException;
use App\Models\RentalCondition;
use App\Models\SimulationSetting;
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

    /**
     * Crée une zone de rayon (distance aller) unique avec un seul tarif journalier,
     * comme le ferait l'utilisateur depuis la page "Conditions" du véhicule.
     */
    private function givenRentalRate(Vehicle $vehicle, ?int $maxKm, int $minDays, ?int $maxDays, float $dailyRate): void
    {
        $condition = RentalCondition::create(['vehicle_id' => $vehicle->id]);
        $zone = $condition->rentalZones()->create(['name' => 'Zone', 'max_km' => $maxKm, 'position' => 0]);
        $zone->rentalRates()->create(['min_days' => $minDays, 'max_days' => $maxDays, 'daily_rate' => $dailyRate]);
    }

    public function test_it_bills_fuel_and_driver_meals_when_they_are_charged_to_the_client(): void
    {
        $vehicle = $this->vehicle();
        $this->givenRentalRate($vehicle, 799, 1, 5, 250000);
        SimulationSetting::current()->update(['fuel_price_per_liter' => 5000, 'driver_meal_price' => 7000]);

        $service = app(SimulationCalculationService::class);

        $simulation = $service->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'meal_charged_to_client' => true,
            'fuel_charged_to_client' => true,
            'same_return_route' => false,
            'legs' => [
                'outbound' => [
                    ['from_point' => 'Antananarivo', 'to_point' => 'Moramanga', 'distance_km' => 120],
                    ['from_point' => 'Moramanga', 'to_point' => 'Toamasina', 'distance_km' => 330],
                ],
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

    public function test_it_bills_nothing_when_the_agency_absorbs_fuel_and_driver_meals(): void
    {
        $vehicle = $this->vehicle();
        $this->givenRentalRate($vehicle, 799, 1, 5, 250000);

        $service = app(SimulationCalculationService::class);

        $simulation = $service->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'meal_charged_to_client' => false,
            'fuel_charged_to_client' => false,
            'same_return_route' => false,
            'legs' => [
                'outbound' => [
                    ['from_point' => 'Antananarivo', 'to_point' => 'Toamasina', 'distance_km' => 450],
                ],
            ],
        ]);

        $this->assertSame('0.00', (string) $simulation->fuel_cost);
        $this->assertSame('0.00', (string) $simulation->meal_cost);
        $this->assertSame('750000.00', (string) $simulation->total);
    }

    public function test_the_two_charges_are_independent(): void
    {
        $vehicle = $this->vehicle();
        $this->givenRentalRate($vehicle, 799, 1, 5, 250000);
        SimulationSetting::current()->update(['fuel_price_per_liter' => 5000, 'driver_meal_price' => 7000]);

        $simulation = app(SimulationCalculationService::class)->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            // Le carburant est refacturé, le repas du chauffeur reste pour l'agence.
            'meal_charged_to_client' => false,
            'fuel_charged_to_client' => true,
            'same_return_route' => false,
            'legs' => [
                'outbound' => [
                    ['from_point' => 'Antananarivo', 'to_point' => 'Toamasina', 'distance_km' => 450],
                ],
            ],
        ]);

        $this->assertSame('225000.00', (string) $simulation->fuel_cost);
        $this->assertSame('0.00', (string) $simulation->meal_cost);
        $this->assertSame('975000.00', (string) $simulation->total);
    }

    public function test_a_departure_time_before_noon_counts_three_meals_per_day(): void
    {
        $vehicle = $this->vehicle();
        $this->givenRentalRate($vehicle, 799, 1, 5, 250000);
        SimulationSetting::current()->update(['fuel_price_per_liter' => 5000, 'driver_meal_price' => 7000]);

        $service = app(SimulationCalculationService::class);

        $simulation = $service->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'departure_time' => '06:00',
            'meal_charged_to_client' => true,
            'fuel_charged_to_client' => false,
            'same_return_route' => false,
            'legs' => [
                'outbound' => [
                    ['from_point' => 'Antananarivo', 'to_point' => 'Toamasina', 'distance_km' => 450],
                ],
            ],
        ]);

        // 3 days x 3 meals x 7000 = 63,000
        $this->assertSame('63000.00', (string) $simulation->meal_cost);
    }

    public function test_it_persists_the_legs_in_order(): void
    {
        $vehicle = $this->vehicle();
        $this->givenRentalRate($vehicle, 799, 1, 5, 250000);

        $service = app(SimulationCalculationService::class);

        $simulation = $service->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'meal_charged_to_client' => false,
            'fuel_charged_to_client' => false,
            'same_return_route' => false,
            'legs' => [
                'outbound' => [
                    ['from_point' => 'Antananarivo', 'to_point' => 'Moramanga', 'distance_km' => 120],
                    ['from_point' => 'Moramanga', 'to_point' => 'Toamasina', 'distance_km' => 200],
                    ['from_point' => 'Toamasina', 'to_point' => 'Antananarivo', 'distance_km' => 300],
                ],
            ],
        ]);

        $legs = $simulation->legs()->orderBy('position')->get();

        $this->assertCount(3, $legs);
        $this->assertSame(['Antananarivo', 'Moramanga', 'Toamasina'], $legs->pluck('from_point')->all());
        $this->assertSame(['outbound', 'outbound', 'outbound'], $legs->pluck('direction')->all());
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
            'meal_charged_to_client' => false,
            'fuel_charged_to_client' => false,
            'same_return_route' => false,
            'legs' => [
                'outbound' => [
                    ['from_point' => 'Antananarivo', 'to_point' => 'Toamasina', 'distance_km' => 450],
                ],
            ],
        ]);
    }

    public function test_same_return_route_mirrors_the_outbound_legs(): void
    {
        $vehicle = $this->vehicle();
        $this->givenRentalRate($vehicle, 400, 1, 10, 250000);

        $service = app(SimulationCalculationService::class);

        $simulation = $service->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 7,
            'meal_charged_to_client' => false,
            'fuel_charged_to_client' => false,
            'same_return_route' => true,
            'legs' => [
                'outbound' => [
                    ['from_point' => 'Antananarivo', 'to_point' => 'Toamasina', 'distance_km' => 400],
                ],
            ],
        ]);

        // aller 400 km (dans la zone) + retour 400 km (miroir) = 800 km au total
        $this->assertSame('800.00', (string) $simulation->distance_km);
        $this->assertSame('1750000.00', (string) $simulation->vehicle_amount);

        $legs = $simulation->legs()->orderBy('position')->get();
        $this->assertCount(2, $legs);
        $this->assertSame('outbound', $legs[0]->direction);
        $this->assertSame('Antananarivo', $legs[0]->from_point);
        $this->assertSame('Toamasina', $legs[0]->to_point);
        $this->assertSame('return', $legs[1]->direction);
        $this->assertSame('Toamasina', $legs[1]->from_point);
        $this->assertSame('Antananarivo', $legs[1]->to_point);
        $this->assertSame('400.00', (string) $legs[1]->distance_km);
    }

    public function test_the_tariff_zone_is_based_on_the_outbound_distance_only(): void
    {
        $vehicle = $this->vehicle();
        // Zone couvrant seulement les trajets aller <= 500 km.
        $this->givenRentalRate($vehicle, 500, 1, 10, 250000);

        $service = app(SimulationCalculationService::class);

        $simulation = $service->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'meal_charged_to_client' => false,
            'fuel_charged_to_client' => false,
            'same_return_route' => false,
            'legs' => [
                'outbound' => [
                    ['from_point' => 'Antananarivo', 'to_point' => 'Toamasina', 'distance_km' => 450],
                ],
                'return' => [
                    ['from_point' => 'Toamasina', 'to_point' => 'Antananarivo', 'distance_km' => 600],
                ],
            ],
        ]);

        // La zone est trouvée grâce aux 450 km de l'aller, même si le retour (600 km)
        // dépasserait la zone à lui seul ; la distance totale sert au carburant.
        $this->assertSame('250000.00', (string) $simulation->daily_rate);
        $this->assertSame('1050.00', (string) $simulation->distance_km);
    }

    public function test_a_400km_outbound_and_return_no_longer_fails_on_the_800km_total(): void
    {
        $vehicle = $this->vehicle();
        $this->givenRentalRate($vehicle, 400, 1, 10, 250000);

        $service = app(SimulationCalculationService::class);

        $simulation = $service->createSimulation([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 7,
            'meal_charged_to_client' => false,
            'fuel_charged_to_client' => false,
            'same_return_route' => false,
            'legs' => [
                'outbound' => [
                    ['from_point' => 'Antananarivo', 'to_point' => 'Toamasina', 'distance_km' => 400],
                ],
                'return' => [
                    ['from_point' => 'Toamasina', 'to_point' => 'Antananarivo', 'distance_km' => 400],
                ],
            ],
        ]);

        $this->assertSame('800.00', (string) $simulation->distance_km);
        $this->assertSame('250000.00', (string) $simulation->daily_rate);
    }
}
