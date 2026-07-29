<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesRentalZones;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRentalConditionRequest extends FormRequest
{
    use ValidatesRentalZones;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->rentalZoneRules();
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
        return $this->rentalZoneAttributes();
    }
}
