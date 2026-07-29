<?php

namespace App\Services;

/**
 * Nombre de repas chauffeur à facturer quand une tranche ne les inclut pas
 * dans son tarif journalier : une location strictement journalière n'en
 * compte qu'un ; au-delà, chaque jour en compte deux ou trois selon que le
 * départ a lieu avant ou après midi.
 */
class DriverMealCalculator
{
    private const MEALS_PER_DAY_BEFORE_NOON = 3;

    private const MEALS_PER_DAY_AFTER_NOON = 2;

    public function mealCount(int $numberOfDays, ?string $departureTime): int
    {
        if ($numberOfDays <= 1) {
            return 1;
        }

        return $numberOfDays * $this->mealsPerDay($departureTime);
    }

    public function mealCost(int $numberOfDays, ?string $departureTime, float $mealPrice): float
    {
        return round($this->mealCount($numberOfDays, $departureTime) * $mealPrice, 2);
    }

    private function mealsPerDay(?string $departureTime): int
    {
        if ($departureTime === null || (int) substr($departureTime, 0, 2) >= 12) {
            return self::MEALS_PER_DAY_AFTER_NOON;
        }

        return self::MEALS_PER_DAY_BEFORE_NOON;
    }
}
