<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['rental_condition_id', 'name', 'max_km', 'position'])]
class RentalZone extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'max_km' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Une zone sans borne haute absorbe tout ce qui dépasse la zone précédente.
     */
    public function isOpenEnded(): bool
    {
        return $this->max_km === null;
    }

    public function covers(float $oneWayKm): bool
    {
        return $this->isOpenEnded() || $oneWayKm <= $this->max_km;
    }

    /**
     * @return BelongsTo<RentalCondition, $this>
     */
    public function rentalCondition(): BelongsTo
    {
        return $this->belongsTo(RentalCondition::class);
    }

    /**
     * @return HasMany<RentalRate, $this>
     */
    public function rentalRates(): HasMany
    {
        return $this->hasMany(RentalRate::class);
    }
}
