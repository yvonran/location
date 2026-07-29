<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'brand', 'model', 'seats', 'registration_number', 'year', 'has_air_conditioning', 'average_consumption', 'status', 'image_path'])]
class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'year' => 'integer',
            'has_air_conditioning' => 'boolean',
            'average_consumption' => 'decimal:2',
            'status' => VehicleStatus::class,
        ];
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return new Attribute(
            get: fn (): ?string => $this->image_path
                ? Storage::disk('public')->url($this->image_path)
                : null,
        );
    }

    public function tariffs(): HasMany
    {
        return $this->hasMany(Tariff::class);
    }

    public function quoteLines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }

    public function reservationLines(): HasMany
    {
        return $this->hasMany(ReservationLine::class);
    }
}
