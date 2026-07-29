<?php

namespace App\Services;

use App\Enums\RentalZone;
use App\Models\RentalCondition;
use App\Models\RentalRate;
use App\Models\Vehicle;

class RentalConditionService
{
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
