<?php

namespace App\Http\Requests;

use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreVehicleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'seats' => ['required', 'integer', 'min:1', 'max:255'],
            'registration_number' => ['required', 'string', 'max:255', Rule::unique('vehicles', 'registration_number')->whereNull('deleted_at')],
            'year' => ['required', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'has_air_conditioning' => ['boolean'],
            'average_consumption' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'status' => ['required', new Enum(VehicleStatus::class)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'brand' => 'marque',
            'model' => 'modèle',
            'seats' => 'nombre de places',
            'registration_number' => 'immatriculation',
            'year' => 'année',
            'has_air_conditioning' => 'climatisation',
            'average_consumption' => 'consommation moyenne',
            'status' => 'statut',
            'image' => 'photo',
        ];
    }
}
