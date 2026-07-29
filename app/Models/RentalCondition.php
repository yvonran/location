<?php

namespace App\Models;

use App\Enums\RentalZone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['vehicle_id', 'city_max_km', 'suburb_max_km', 'long_distance_max_km'])]
class RentalCondition extends Model
{
    use HasFactory;

    public const DEFAULT_CITY_MAX_KM = 50;

    public const DEFAULT_SUBURB_MAX_KM = 100;

    public const DEFAULT_LONG_DISTANCE_MAX_KM = 700;

    /**
     * Les valeurs par défaut des colonnes ne s'appliquent qu'à l'insertion :
     * on les répète ici pour qu'une instance non enregistrée les porte déjà.
     */
    protected $attributes = [
        'city_max_km' => self::DEFAULT_CITY_MAX_KM,
        'suburb_max_km' => self::DEFAULT_SUBURB_MAX_KM,
        'long_distance_max_km' => self::DEFAULT_LONG_DISTANCE_MAX_KM,
    ];

    protected function casts(): array
    {
        return [
            'city_max_km' => 'integer',
            'suburb_max_km' => 'integer',
            'long_distance_max_km' => 'integer',
        ];
    }

    /**
     * Zone correspondant à la distance du trajet aller, en km.
     */
    public function zoneFor(float $oneWayKm): RentalZone
    {
        return match (true) {
            $oneWayKm <= $this->city_max_km => RentalZone::City,
            $oneWayKm <= $this->suburb_max_km => RentalZone::Suburb,
            $oneWayKm <= $this->long_distance_max_km => RentalZone::LongDistance,
            default => RentalZone::VeryLongDistance,
        };
    }

    /**
     * Borne haute du trajet aller pour une zone, ou null pour la zone ouverte.
     */
    public function maxKmFor(RentalZone $zone): ?int
    {
        return match ($zone) {
            RentalZone::City => $this->city_max_km,
            RentalZone::Suburb => $this->suburb_max_km,
            RentalZone::LongDistance => $this->long_distance_max_km,
            RentalZone::VeryLongDistance => null,
        };
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return HasMany<RentalRate, $this>
     */
    public function rentalRates(): HasMany
    {
        return $this->hasMany(RentalRate::class);
    }
}
