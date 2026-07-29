<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends StoreVehicleRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'registration_number' => [
                'required', 'string', 'max:255',
                Rule::unique('vehicles', 'registration_number')
                    ->ignore($this->route('vehicle'))
                    ->whereNull('deleted_at'),
            ],
            'remove_image' => ['boolean'],
        ]);
    }
}
