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
            'meal_charged_to_client' => ['boolean'],
            'fuel_charged_to_client' => ['boolean'],
            'same_return_route' => ['boolean'],
            'legs.outbound' => ['required', 'array', 'min:1'],
            'legs.outbound.*.from_point' => ['required', 'string', 'max:255'],
            'legs.outbound.*.to_point' => ['required', 'string', 'max:255'],
            'legs.outbound.*.distance_km' => ['required', 'numeric', 'min:0'],
            'legs.return' => ['required_if:same_return_route,false', 'array', 'min:1'],
            'legs.return.*.from_point' => ['required', 'string', 'max:255'],
            'legs.return.*.to_point' => ['required', 'string', 'max:255'],
            'legs.return.*.distance_km' => ['required', 'numeric', 'min:0'],
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
            'meal_charged_to_client' => 'repas chauffeur à la charge du client',
            'fuel_charged_to_client' => 'carburant à la charge du client',
            'legs.outbound' => 'trajet aller',
            'legs.outbound.*.from_point' => 'point de départ',
            'legs.outbound.*.to_point' => 'point d\'arrivée',
            'legs.outbound.*.distance_km' => 'kilométrage aller',
            'legs.return' => 'trajet retour',
            'legs.return.*.from_point' => 'point de départ (retour)',
            'legs.return.*.to_point' => 'point d\'arrivée (retour)',
            'legs.return.*.distance_km' => 'kilométrage retour',
        ];
    }
}
