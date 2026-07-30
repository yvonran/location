<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'vehicle_id', 'number_of_days', 'departure_time', 'distance_km', 'same_return_route', 'daily_rate',
    'meal_included', 'fuel_included', 'meal_cost', 'fuel_cost', 'vehicle_amount', 'total',
])]
class Simulation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'number_of_days' => 'integer',
            'distance_km' => 'decimal:2',
            'same_return_route' => 'boolean',
            'daily_rate' => 'decimal:2',
            'meal_included' => 'boolean',
            'fuel_included' => 'boolean',
            'meal_cost' => 'decimal:2',
            'fuel_cost' => 'decimal:2',
            'vehicle_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return HasMany<SimulationLeg, $this>
     */
    public function legs(): HasMany
    {
        return $this->hasMany(SimulationLeg::class);
    }
}
