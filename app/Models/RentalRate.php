<?php

namespace App\Models;

use App\Enums\RentalZone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['rental_condition_id', 'zone', 'min_days', 'max_days', 'daily_rate'])]
class RentalRate extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'zone' => RentalZone::class,
            'min_days' => 'integer',
            'max_days' => 'integer',
            'daily_rate' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<RentalCondition, $this>
     */
    public function rentalCondition(): BelongsTo
    {
        return $this->belongsTo(RentalCondition::class);
    }
}
