<?php

namespace App\Http\Requests\Configuration;

use Illuminate\Validation\Rule;

class UpdateVehicleModelRequest extends StoreVehicleModelRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('vehicle_models', 'name')
                    ->where('brand_id', $this->integer('brand_id'))
                    ->ignore($this->route('vehicle_model')),
            ],
        ];
    }
}
