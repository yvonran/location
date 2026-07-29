<?php

namespace App\Services;

use App\Exceptions\NoTariffFoundException;
use App\Models\Simulation;
use App\Models\SimulationSetting;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class SimulationCalculationService
{
    public function __construct(
        private readonly TariffService $tariffService,
        private readonly DriverMealCalculator $mealCalculator,
    ) {}

    public function createSimulation(array $input): Simulation
    {
        return DB::transaction(function () use ($input) {
            $calculated = $this->calculate($input);
            $legs = $calculated['legs'];
            unset($calculated['legs']);

            $simulation = Simulation::create($calculated);

            foreach ($legs as $position => $leg) {
                $simulation->legs()->create([...$leg, 'position' => $position]);
            }

            return $simulation->fresh('legs');
        });
    }

    /**
     * @return array{
     *     vehicle_id: int, number_of_days: int, departure_time: ?string, distance_km: float,
     *     daily_rate: float, meal_included: bool, fuel_included: bool, meal_cost: float,
     *     fuel_cost: float, vehicle_amount: float, total: float, legs: array
     * }
     */
    public function calculate(array $input): array
    {
        $vehicle = Vehicle::findOrFail($input['vehicle_id']);
        $numberOfDays = (int) $input['number_of_days'];
        $departureTime = $input['departure_time'] ?? null;
        $mealIncluded = (bool) ($input['meal_included'] ?? false);
        $fuelIncluded = (bool) ($input['fuel_included'] ?? false);

        $legs = array_values($input['legs'] ?? []);
        $distanceKm = round(array_sum(array_map(
            fn (array $leg) => (float) $leg['distance_km'],
            $legs,
        )), 2);

        $tariff = $this->tariffService->findRate($vehicle, $distanceKm, $numberOfDays);

        if (! $tariff) {
            throw new NoTariffFoundException(
                "Aucun tarif trouvé pour {$vehicle->name} sur {$distanceKm} km et {$numberOfDays} jour(s)."
            );
        }

        $dailyRate = (float) $tariff->daily_rate;
        $vehicleAmount = round($dailyRate * $numberOfDays, 2);

        $settings = SimulationSetting::current();

        $fuelCost = $fuelIncluded ? 0.0 : round(
            ((float) ($vehicle->average_consumption ?? 0) / 100) * $distanceKm * (float) $settings->fuel_price_per_liter,
            2,
        );

        $mealCost = $mealIncluded ? 0.0 : $this->mealCalculator->mealCost(
            $numberOfDays,
            $departureTime,
            (float) $settings->client_meal_price,
        );

        return [
            'vehicle_id' => $vehicle->id,
            'number_of_days' => $numberOfDays,
            'departure_time' => $departureTime,
            'distance_km' => $distanceKm,
            'daily_rate' => $dailyRate,
            'meal_included' => $mealIncluded,
            'fuel_included' => $fuelIncluded,
            'meal_cost' => $mealCost,
            'fuel_cost' => $fuelCost,
            'vehicle_amount' => $vehicleAmount,
            'total' => round($vehicleAmount + $fuelCost + $mealCost, 2),
            'legs' => array_map(fn (array $leg) => [
                'from_point' => $leg['from_point'],
                'to_point' => $leg['to_point'],
                'distance_km' => (float) $leg['distance_km'],
            ], $legs),
        ];
    }
}
