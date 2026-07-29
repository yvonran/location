<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['rental_zone_id', 'min_days', 'max_days', 'daily_rate', 'meal_included', 'meal_price'])]
class RentalRate extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'min_days' => 'integer',
            'max_days' => 'integer',
            'daily_rate' => 'decimal:2',
            'meal_included' => 'boolean',
            'meal_price' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<RentalZone, $this>
     */
    public function rentalZone(): BelongsTo
    {
        return $this->belongsTo(RentalZone::class);
    }
}
