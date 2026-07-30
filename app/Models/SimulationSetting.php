<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['fuel_price_per_liter', 'driver_meal_price'])]
class SimulationSetting extends Model
{
    protected function casts(): array
    {
        return [
            'fuel_price_per_liter' => 'decimal:2',
            'driver_meal_price' => 'decimal:2',
        ];
    }

    /**
     * Il n'existe qu'une seule ligne de réglages : on la crée avec des valeurs
     * de départ raisonnables si elle n'existe pas encore.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'fuel_price_per_liter' => 5000,
            'driver_meal_price' => 7000,
        ]);
    }
}
