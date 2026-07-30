<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'name', 'vehicle_model_id', 'seats', 'registration_number', 'year', 'has_air_conditioning', 'average_consumption', 'status', 'image_path'])]
class Vehicle extends Model
{
    use BelongsToUser, HasPublicUid, HasFactory, SoftDeletes;

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

    /**
     * @return BelongsTo<VehicleModel, $this>
     */
    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    /**
     * Charge le modèle avec sa marque et son type : c'est ce qu'il faut pour
     * afficher un véhicule sans requête supplémentaire.
     *
     * @param  Builder<Vehicle>  $query
     */
    public function scopeWithIdentity(Builder $query): void
    {
        $query->with('vehicleModel.brand', 'vehicleModel.vehicleType');
    }

    /**
     * @return HasOne<RentalCondition, $this>
     */
    public function rentalCondition(): HasOne
    {
        return $this->hasOne(RentalCondition::class);
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
