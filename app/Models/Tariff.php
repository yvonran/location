<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vehicle_id', 'min_distance_km', 'max_distance_km', 'min_days', 'max_days', 'daily_rate'])]
class Tariff extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'min_distance_km' => 'integer',
            'max_distance_km' => 'integer',
            'min_days' => 'integer',
            'max_days' => 'integer',
            'daily_rate' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
