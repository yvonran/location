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
        private readonly RentalConditionService $rentalConditionService,
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
     *     daily_rate: float, meal_charged_to_client: bool, fuel_charged_to_client: bool, meal_cost: float,
     *     fuel_cost: float, vehicle_amount: float, total: float, same_return_route: bool, legs: array
     * }
     */
    public function calculate(array $input): array
    {
        $vehicle = Vehicle::findOrFail($input['vehicle_id']);
        $numberOfDays = (int) $input['number_of_days'];
        $departureTime = $input['departure_time'] ?? null;
        $mealChargedToClient = (bool) ($input['meal_charged_to_client'] ?? false);
        $fuelChargedToClient = (bool) ($input['fuel_charged_to_client'] ?? false);
        $sameReturnRoute = (bool) ($input['same_return_route'] ?? false);

        $legsInput = $input['legs'] ?? [];
        $outboundLegs = array_values($legsInput['outbound'] ?? []);

        $returnLegs = $sameReturnRoute
            ? array_map(fn (array $leg) => [
                'from_point' => $leg['to_point'],
                'to_point' => $leg['from_point'],
                'distance_km' => (float) $leg['distance_km'],
            ], array_reverse($outboundLegs))
            : array_values($legsInput['return'] ?? []);

        $outboundDistanceKm = round(array_sum(array_map(
            fn (array $leg) => (float) $leg['distance_km'],
            $outboundLegs,
        )), 2);
        $returnDistanceKm = round(array_sum(array_map(
            fn (array $leg) => (float) $leg['distance_km'],
            $returnLegs,
        )), 2);
        $distanceKm = round($outboundDistanceKm + $returnDistanceKm, 2);

        $tariff = $this->rentalConditionService->findRate($vehicle, $outboundDistanceKm, $numberOfDays);

        if (! $tariff) {
            throw new NoTariffFoundException(
                "Aucun tarif trouvé pour {$vehicle->name} sur {$outboundDistanceKm} km (aller) et {$numberOfDays} jour(s)."
            );
        }

        $dailyRate = (float) $tariff->daily_rate;
        $vehicleAmount = round($dailyRate * $numberOfDays, 2);

        $settings = SimulationSetting::current();

        // Seul ce qui est à la charge du client est facturé ; le reste est
        // absorbé par l'agence et vaut donc zéro dans la simulation.
        $fuelCost = $fuelChargedToClient ? round(
            ((float) ($vehicle->average_consumption ?? 0) / 100) * $distanceKm * (float) $settings->fuel_price_per_liter,
            2,
        ) : 0.0;

        $mealCost = $mealChargedToClient ? $this->mealCalculator->mealCost(
            $numberOfDays,
            $departureTime,
            (float) $settings->driver_meal_price,
        ) : 0.0;

        $legs = [
            ...array_map(fn (array $leg) => [
                'from_point' => $leg['from_point'],
                'to_point' => $leg['to_point'],
                'distance_km' => (float) $leg['distance_km'],
                'direction' => 'outbound',
            ], $outboundLegs),
            ...array_map(fn (array $leg) => [
                'from_point' => $leg['from_point'],
                'to_point' => $leg['to_point'],
                'distance_km' => (float) $leg['distance_km'],
                'direction' => 'return',
            ], $returnLegs),
        ];

        return [
            'vehicle_id' => $vehicle->id,
            'number_of_days' => $numberOfDays,
            'departure_time' => $departureTime,
            'distance_km' => $distanceKm,
            'daily_rate' => $dailyRate,
            'meal_charged_to_client' => $mealChargedToClient,
            'fuel_charged_to_client' => $fuelChargedToClient,
            'meal_cost' => $mealCost,
            'fuel_cost' => $fuelCost,
            'vehicle_amount' => $vehicleAmount,
            'total' => round($vehicleAmount + $fuelCost + $mealCost, 2),
            'same_return_route' => $sameReturnRoute,
            'legs' => $legs,
        ];
    }
}
