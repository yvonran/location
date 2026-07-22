<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'departure_city', 'arrival_city', 'distance_km', 'estimated_duration_minutes', 'description'])]
class Route extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'estimated_duration_minutes' => 'integer',
        ];
    }

    public function quoteLines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }
}
