<?php

namespace App\Http\Requests\Configuration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSimulationSettingRequest extends FormRequest
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
            'fuel_price_per_liter' => ['required', 'numeric', 'min:0'],
            'driver_meal_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'fuel_price_per_liter' => 'prix du litre de carburant',
            'driver_meal_price' => 'prix du repas chauffeur',
        ];
    }
}
