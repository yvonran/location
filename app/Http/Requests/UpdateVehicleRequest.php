<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends StoreVehicleRequest
{
    /**
     * Les conditions de location ont leur propre formulaire une fois le
     * véhicule créé : la fiche seule est soumise ici.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->vehicleRules(),
            'registration_number' => [
                'required', 'string', 'max:255',
                Rule::unique('vehicles', 'registration_number')
                    ->ignore($this->route('vehicle'))
                    ->whereNull('deleted_at'),
            ],
            'remove_image' => ['boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [];
    }
}
