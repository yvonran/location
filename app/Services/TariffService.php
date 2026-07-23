<?php

namespace App\Services;

use App\Models\Tariff;
use App\Models\Vehicle;

class TariffService
{
    public function findRate(Vehicle $vehicle, float $distanceKm, int $numberOfDays): ?Tariff
    {
        return Tariff::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('min_distance_km', '<=', $distanceKm)
            ->where(function ($query) use ($distanceKm) {
                $query->whereNull('max_distance_km')->orWhere('max_distance_km', '>=', $distanceKm);
            })
            ->where('min_days', '<=', $numberOfDays)
            ->where(function ($query) use ($numberOfDays) {
                $query->whereNull('max_days')->orWhere('max_days', '>=', $numberOfDays);
            })
            ->first();
    }
}
