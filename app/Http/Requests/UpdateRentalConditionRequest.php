<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

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
            'zones' => ['required', 'array', 'min:1'],
            'zones.*.name' => ['required', 'string', 'max:255'],
            'zones.*.max_km' => ['nullable', 'integer', 'min:1'],
            'zones.*.rates' => ['array'],
            'zones.*.rates.*.min_days' => ['required', 'integer', 'min:1'],
            'zones.*.rates.*.max_days' => ['nullable', 'integer', 'gte:zones.*.rates.*.min_days'],
            'zones.*.rates.*.daily_rate' => ['required', 'numeric', 'min:0'],
        ];
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
     * Les bornes doivent croître, et seule la dernière zone peut rester ouverte :
     * sans cela une distance pourrait relever de deux zones, ou d'aucune.
     */
    private function validateZoneBounds(Validator $validator): void
    {
        $zones = array_values((array) $this->input('zones', []));
        $lastIndex = count($zones) - 1;
        $previousMax = 0;

        foreach ($zones as $index => $zone) {
            $maxKm = $zone['max_km'] ?? null;
            $isLast = $index === $lastIndex;

            if ($isLast) {
                if ($maxKm !== null && $maxKm !== '') {
                    $validator->errors()->add(
                        "zones.{$index}.max_km",
                        'La dernière zone doit rester sans limite pour couvrir les plus longs trajets.',
                    );
                }

                continue;
            }

            if ($maxKm === null || $maxKm === '') {
                $validator->errors()->add(
                    "zones.{$index}.max_km",
                    'Seule la dernière zone peut être sans limite.',
                );

                continue;
            }

            if ((int) $maxKm <= $previousMax) {
                $validator->errors()->add(
                    "zones.{$index}.max_km",
                    "Cette borne doit dépasser celle de la zone précédente ({$previousMax} km).",
                );
            }

            $previousMax = (int) $maxKm;
        }
    }

    /**
     * Deux tranches de durée qui se chevauchent dans une même zone rendraient
     * le tarif applicable ambigu : on les refuse à la saisie.
     */
    private function rejectOverlappingDayRanges(Validator $validator): void
    {
        foreach ((array) $this->input('zones', []) as $zoneIndex => $zone) {
            $ranges = [];

            foreach ((array) ($zone['rates'] ?? []) as $rateIndex => $rate) {
                if (! isset($rate['min_days'])) {
                    continue;
                }

                $maxDays = $rate['max_days'] ?? null;

                $ranges[] = [
                    'index' => $rateIndex,
                    'min' => (int) $rate['min_days'],
                    'max' => ($maxDays === null || $maxDays === '') ? PHP_INT_MAX : (int) $maxDays,
                ];
            }

            foreach ($ranges as $position => $range) {
                foreach (array_slice($ranges, $position + 1) as $other) {
                    if ($range['min'] <= $other['max'] && $other['min'] <= $range['max']) {
                        $validator->errors()->add(
                            "zones.{$zoneIndex}.rates.{$other['index']}.min_days",
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
            'zones.*.name' => 'nom de la zone',
            'zones.*.max_km' => 'borne de la zone',
        ];
    }
}
