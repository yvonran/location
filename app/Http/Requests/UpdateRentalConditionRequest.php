<?php

namespace App\Http\Requests;

use App\Enums\RentalZone;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateRentalConditionRequest extends FormRequest
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
            'city_max_km' => ['required', 'integer', 'min:1'],
            'suburb_max_km' => ['required', 'integer', 'gt:city_max_km'],
            'long_distance_max_km' => ['required', 'integer', 'gt:suburb_max_km'],
            'rates' => ['array'],
            'rates.*.zone' => ['required', new Enum(RentalZone::class)],
            'rates.*.min_days' => ['required', 'integer', 'min:1'],
            'rates.*.max_days' => ['nullable', 'integer', 'gte:rates.*.min_days'],
            'rates.*.daily_rate' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->rejectOverlappingDayRanges($validator),
        ];
    }

    /**
     * Deux tranches de durée qui se chevauchent dans une même zone rendraient
     * le tarif applicable ambigu : on les refuse à la saisie.
     */
    private function rejectOverlappingDayRanges(Validator $validator): void
    {
        $rangesByZone = [];

        foreach ((array) $this->input('rates', []) as $index => $rate) {
            if (! isset($rate['zone'], $rate['min_days'])) {
                continue;
            }

            $maxDays = $rate['max_days'] ?? null;

            $rangesByZone[$rate['zone']][] = [
                'index' => $index,
                'min' => (int) $rate['min_days'],
                'max' => ($maxDays === null || $maxDays === '') ? PHP_INT_MAX : (int) $maxDays,
            ];
        }

        foreach ($rangesByZone as $ranges) {
            foreach ($ranges as $position => $range) {
                foreach (array_slice($ranges, $position + 1) as $other) {
                    if ($range['min'] <= $other['max'] && $other['min'] <= $range['max']) {
                        $validator->errors()->add(
                            "rates.{$other['index']}.min_days",
                            'Cette tranche de durée en chevauche une autre dans la même zone.',
                        );
                    }
                }
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'city_max_km' => 'limite ville',
            'suburb_max_km' => 'limite périphérie',
            'long_distance_max_km' => 'limite longue distance',
        ];
    }
}
