<?php

namespace App\Services;

use App\Enums\RentalZone;
use App\Models\RentalCondition;
use App\Models\RentalRate;
use App\Models\Vehicle;

class RentalConditionService
{
    /**
     * Props du formulaire de conditions, partagées par la page dédiée et la page
     * d'édition du véhicule. Un véhicule sans conditions reçoit les seuils par défaut.
     *
     * @return array{condition: array{city_max_km: int, suburb_max_km: int, long_distance_max_km: int}, rates: iterable<int, RentalRate>, zones: array<int, array{value: string, label: string}>}
     */
    public function editorProps(Vehicle $vehicle): array
    {
        $condition = $vehicle->rentalCondition ?? new RentalCondition;

        return [
            'condition' => [
                'city_max_km' => $condition->city_max_km,
                'suburb_max_km' => $condition->suburb_max_km,
                'long_distance_max_km' => $condition->long_distance_max_km,
            ],
            'rates' => $condition->exists
                ? $condition->rentalRates()->orderBy('zone')->orderBy('min_days')->get()
                : [],
            'zones' => array_map(
                fn (RentalZone $zone) => ['value' => $zone->value, 'label' => $zone->label()],
                RentalZone::cases(),
            ),
        ];
    }

    /**
     * Zone applicable au trajet aller, ou null si le véhicule n'a pas de conditions de location.
     */
    public function zoneFor(Vehicle $vehicle, float $oneWayKm): ?RentalZone
    {
        return $vehicle->rentalCondition?->zoneFor($oneWayKm);
    }

    /**
     * Tarif journalier applicable au trajet aller et à la durée demandée.
     */
    public function findRate(Vehicle $vehicle, float $oneWayKm, int $numberOfDays): ?RentalRate
    {
        $condition = $vehicle->rentalCondition;

        if (! $condition instanceof RentalCondition) {
            return null;
        }

        return $condition->rentalRates()
            ->where('zone', $condition->zoneFor($oneWayKm))
            ->where('min_days', '<=', $numberOfDays)
            ->where(function ($query) use ($numberOfDays) {
                $query->whereNull('max_days')->orWhere('max_days', '>=', $numberOfDays);
            })
            ->orderBy('min_days')
            ->first();
    }
}
