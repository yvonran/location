<?php

namespace App\Http\Requests;

use App\Enums\VehicleStatus;
use App\Http\Requests\Concerns\ValidatesRentalZones;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreVehicleRequest extends FormRequest
{
    use ValidatesRentalZones;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Champs de la fiche véhicule, communs à la création et à la modification.
     *
     * @return array<string, mixed>
     */
    protected function vehicleRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // La marque découle du modèle choisi : elle n'est pas saisie séparément.
            'vehicle_model_id' => ['required', 'integer', 'exists:vehicle_models,id'],
            'seats' => ['required', 'integer', 'min:1', 'max:255'],
            'registration_number' => ['required', 'string', 'max:255', Rule::unique('vehicles', 'registration_number')->whereNull('deleted_at')],
            'year' => ['required', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'has_air_conditioning' => ['boolean'],
            'average_consumption' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'status' => ['required', new Enum(VehicleStatus::class)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    /**
     * Le véhicule n'existant pas encore, ses conditions de location sont
     * enregistrées dans la même requête que sa fiche.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [...$this->vehicleRules(), ...$this->rentalZoneRules()];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateZoneBounds($validator),
            fn (Validator $validator) => $this->rejectOverlappingDayRanges($validator),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'vehicle_model_id' => 'modèle',
            'seats' => 'nombre de places',
            'registration_number' => 'immatriculation',
            'year' => 'année',
            'has_air_conditioning' => 'climatisation',
            'average_consumption' => 'consommation moyenne',
            'status' => 'statut',
            'image' => 'photo',
            ...$this->rentalZoneAttributes(),
        ];
    }
}
