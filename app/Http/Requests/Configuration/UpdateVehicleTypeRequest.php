<?php

namespace App\Http\Requests\Configuration;

use Illuminate\Validation\Rule;

class UpdateVehicleTypeRequest extends StoreVehicleTypeRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('vehicle_types', 'name')->ignore($this->route('vehicle_type')),
            ],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
