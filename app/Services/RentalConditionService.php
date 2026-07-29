<?php

namespace App\Services;

use App\Models\RentalCondition;
use App\Models\RentalRate;
use App\Models\RentalZone;
use App\Models\Vehicle;

class RentalConditionService
{
    /**
     * Props du formulaire de conditions, partagées par la page dédiée et la page
     * d'édition du véhicule. Un véhicule sans conditions reçoit le découpage
     * proposé par défaut, qu'il reste libre de modifier avant d'enregistrer.
     *
     * @return array{zones: array<int, array{name: string, max_km: int|null, rates: array<int, array{min_days: int, max_days: int|null, daily_rate: string}>}>}
     */
    public function editorProps(Vehicle $vehicle): array
    {
        $condition = $vehicle->rentalCondition;

        if (! $condition instanceof RentalCondition) {
            return ['zones' => $this->defaultZones()];
        }

        $zones = $condition->rentalZones()
            ->with(['rentalRates' => fn ($query) => $query->orderBy('min_days')])
            ->orderBy('position')
            ->get()
            ->map(fn (RentalZone $zone) => [
                'name' => $zone->name,
                'max_km' => $zone->max_km,
                'rates' => $zone->rentalRates
                    ->map(fn (RentalRate $rate) => [
                        'min_days' => $rate->min_days,
                        'max_days' => $rate->max_days,
                        'daily_rate' => $rate->daily_rate,
                    ])
                    ->all(),
            ])
            ->all();

        return ['zones' => $zones === [] ? $this->defaultZones() : $zones];
    }

    /**
     * @return array<int, array{name: string, max_km: int|null, rates: array<int, mixed>}>
     */
    private function defaultZones(): array
    {
        return array_map(
            fn (array $zone) => ['name' => $zone[0], 'max_km' => $zone[1], 'rates' => []],
            RentalCondition::DEFAULT_ZONES,
        );
    }

    /**
     * Zone applicable au trajet aller, ou null si le véhicule n'a pas de
     * conditions ou qu'aucune zone ne couvre cette distance.
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
        $zone = $this->zoneFor($vehicle, $oneWayKm);

        if (! $zone instanceof RentalZone) {
            return null;
        }

        return $zone->rentalRates()
            ->where('min_days', '<=', $numberOfDays)
            ->where(function ($query) use ($numberOfDays) {
                $query->whereNull('max_days')->orWhere('max_days', '>=', $numberOfDays);
            })
            ->orderBy('min_days')
            ->first();
    }
}
