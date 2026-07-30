<?php

namespace App\Services;

use App\Enums\AmountMode;
use App\Exceptions\NoTariffFoundException;
use App\Models\OptionType;
use App\Models\Quote;
use App\Models\Route;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class QuoteCalculationService
{
    public function __construct(private readonly RentalConditionService $rentalConditionService) {}

    public function createQuote(int $customerId, int $userId, array $linesInput, ?string $notes = null): Quote
    {
        return DB::transaction(function () use ($customerId, $userId, $linesInput, $notes) {
            $quote = Quote::create([
                'number' => $this->nextQuoteNumber(),
                'customer_id' => $customerId,
                'user_id' => $userId,
                'quote_date' => now()->toDateString(),
                'notes' => $notes,
            ]);

            $subtotal = 0.0;

            foreach (array_values($linesInput) as $position => $lineInput) {
                $calculated = $this->calculateLine($lineInput);
                $options = $calculated['options'];
                unset($calculated['options']);

                $line = $quote->quoteLines()->create([
                    ...$calculated,
                    'position' => $position,
                ]);

                foreach ($options as $option) {
                    $line->quoteLineOptions()->create($option);
                }

                $subtotal += $calculated['line_total'];
            }

            $quote->update([
                'subtotal' => round($subtotal, 2),
                'total' => round($subtotal, 2),
            ]);

            return $quote->fresh(['quoteLines.quoteLineOptions']);
        });
    }

    /**
     * @return array{
     *     vehicle_id: int, route_id: ?int, service_type_id: int, start_date: string, departure_time: ?string,
     *     number_of_days: int, distance_km: float, daily_rate: float, service_coefficient: float,
     *     discount_type: ?AmountMode, discount_value: ?float, discount_amount: float,
     *     options_amount: float, line_total: float, options: array
     * }
     */
    public function calculateLine(array $input): array
    {
        $vehicle = Vehicle::findOrFail($input['vehicle_id']);
        $serviceType = ServiceType::findOrFail($input['service_type_id']);

        if (! empty($input['route_id'])) {
            $distanceKm = (float) Route::findOrFail($input['route_id'])->distance_km;
        } else {
            $distanceKm = (float) $input['distance_km'];
        }

        $numberOfDays = (int) $input['number_of_days'];

        $tariff = $this->rentalConditionService->findRate($vehicle, $distanceKm, $numberOfDays);

        if (! $tariff) {
            throw new NoTariffFoundException(
                "Aucun tarif trouvé pour {$vehicle->name} sur {$distanceKm} km et {$numberOfDays} jour(s)."
            );
        }

        $dailyRate = (float) $tariff->daily_rate;
        $serviceCoefficient = (float) $serviceType->coefficient;
        $serviceAmount = round($dailyRate * $numberOfDays * $serviceCoefficient, 2);

        [$options, $optionsAmount] = $this->calculateOptions($input['options'] ?? [], $serviceAmount);

        $baseForDiscount = round($serviceAmount + $optionsAmount, 2);
        [$discountType, $discountValue, $discountAmount] = $this->calculateDiscount($input, $baseForDiscount);

        return [
            'vehicle_id' => $vehicle->id,
            'route_id' => $input['route_id'] ?? null,
            'service_type_id' => $serviceType->id,
            'start_date' => $input['start_date'],
            'departure_time' => $input['departure_time'] ?? null,
            'number_of_days' => $numberOfDays,
            'distance_km' => $distanceKm,
            'daily_rate' => $dailyRate,
            'service_coefficient' => $serviceCoefficient,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_amount' => $discountAmount,
            'options_amount' => $optionsAmount,
            'line_total' => round($baseForDiscount - $discountAmount, 2),
            'options' => $options,
        ];
    }

    private function calculateOptions(array $optionsInput, float $serviceAmount): array
    {
        $options = [];
        $optionsAmount = 0.0;

        foreach ($optionsInput as $optionInput) {
            $optionType = OptionType::findOrFail($optionInput['option_type_id']);
            $mode = AmountMode::from($optionInput['mode'] ?? $optionType->default_mode->value);
            $value = (float) ($optionInput['value'] ?? $optionType->default_value);

            $amount = $mode === AmountMode::Percentage
                ? round($serviceAmount * $value / 100, 2)
                : round($value, 2);

            $options[] = [
                'option_type_id' => $optionType->id,
                'mode' => $mode,
                'value' => $value,
                'amount' => $amount,
            ];

            $optionsAmount += $amount;
        }

        return [$options, round($optionsAmount, 2)];
    }

    private function calculateDiscount(array $input, float $baseForDiscount): array
    {
        if (empty($input['discount_type']) || ! isset($input['discount_value'])) {
            return [null, null, 0.0];
        }

        $discountType = AmountMode::from($input['discount_type']);
        $discountValue = (float) $input['discount_value'];

        $discountAmount = $discountType === AmountMode::Percentage
            ? round($baseForDiscount * $discountValue / 100, 2)
            : round($discountValue, 2);

        return [$discountType, $discountValue, $discountAmount];
    }

    private function nextQuoteNumber(): string
    {
        $year = (int) now()->year;
        $count = Quote::withTrashed()->whereYear('quote_date', $year)->count() + 1;

        return sprintf('QUO-%d-%04d', $year, $count);
    }
}
