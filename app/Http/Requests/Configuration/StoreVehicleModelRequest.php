<?php

namespace App\Http\Requests\Configuration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'vehicle_type_id' => ['nullable', 'integer', 'exists:vehicle_types,id'],
            'name' => [
                'required', 'string', 'max:255',
                // Un même nom de modèle peut exister chez deux marques différentes.
                Rule::unique('vehicle_models', 'name')->where('brand_id', $this->integer('brand_id')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'brand_id' => 'marque',
            'vehicle_type_id' => 'type',
            'name' => 'nom du modèle',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Cette marque possède déjà un modèle portant ce nom.',
        ];
    }
}
