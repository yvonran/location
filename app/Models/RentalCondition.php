<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['vehicle_id'])]
class RentalCondition extends Model
{
    use HasFactory;

    /**
     * Découpage proposé à la création : l'utilisateur le renomme, le complète
     * ou le réduit à sa guise. Chaque entrée est [nom, borne haute du trajet aller].
     */
    public const DEFAULT_ZONES = [
        ['Ville', 50],
        ['Périphérie', 100],
        ['Longue distance', 700],
        ['Très longue distance', null],
    ];

    /**
     * Zone couvrant la distance du trajet aller, ou null si aucune ne convient
     * (cas d'un découpage sans zone ouverte laissant un trou au-delà du dernier seuil).
     */
    public function zoneFor(float $oneWayKm): ?RentalZone
    {
        return $this->rentalZones()
            ->orderBy('position')
            ->get()
            ->first(fn (RentalZone $zone) => $zone->covers($oneWayKm));
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return HasMany<RentalZone, $this>
     */
    public function rentalZones(): HasMany
    {
        return $this->hasMany(RentalZone::class);
    }
}
