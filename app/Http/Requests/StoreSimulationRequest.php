<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSimulationRequest extends FormRequest
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
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'number_of_days' => ['required', 'integer', 'min:1'],
            'departure_time' => ['nullable', 'date_format:H:i'],
            'meal_included' => ['boolean'],
            'fuel_included' => ['boolean'],
            'legs' => ['required', 'array', 'min:1'],
            'legs.*.from_point' => ['required', 'string', 'max:255'],
            'legs.*.to_point' => ['required', 'string', 'max:255'],
            'legs.*.distance_km' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'vehicle_id' => 'véhicule',
            'number_of_days' => 'nombre de jours',
            'departure_time' => 'heure de départ',
            'legs' => 'trajet',
            'legs.*.from_point' => 'point de départ',
            'legs.*.to_point' => 'point d\'arrivée',
            'legs.*.distance_km' => 'kilométrage',
        ];
    }
}
