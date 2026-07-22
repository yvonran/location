<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'brand', 'model', 'seats', 'registration_number', 'year', 'has_air_conditioning', 'status'])]
class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'year' => 'integer',
            'has_air_conditioning' => 'boolean',
            'status' => VehicleStatus::class,
        ];
    }

    public function tariffs(): HasMany
    {
        return $this->hasMany(Tariff::class);
    }
}
