<?php

namespace Tests\Feature\Services;

use App\Enums\AmountMode;
use App\Exceptions\NoTariffFoundException;
use App\Models\Customer;
use App\Models\OptionType;
use App\Models\RentalCondition;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Services\QuoteCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function givenRentalRate(Vehicle $vehicle, ?int $maxKm, int $minDays, ?int $maxDays, float $dailyRate): void
    {
        $condition = RentalCondition::create(['vehicle_id' => $vehicle->id]);
        $zone = $condition->rentalZones()->create(['name' => 'Zone', 'max_km' => $maxKm, 'position' => 0]);
        $zone->rentalRates()->create(['min_days' => $minDays, 'max_days' => $maxDays, 'daily_rate' => $dailyRate]);
    }

    public function test_it_calculates_a_line_with_service_coefficient_option_and_discount(): void
    {
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);

        $this->givenRentalRate($vehicle, 799, 1, 5, 250000);

        $serviceType = ServiceType::create(['name' => 'Transfert', 'coefficient' => 2]);
        $optionType = OptionType::create([
            'name' => 'Assurance', 'default_mode' => AmountMode::Percentage, 'default_value' => 5,
        ]);
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $user = User::factory()->create();

        $service = app(QuoteCalculationService::class);

        $quote = $service->createQuote($customer->id, $user->id, [
            [
                'vehicle_id' => $vehicle->id,
                'route_id' => null,
                'distance_km' => 450,
                'service_type_id' => $serviceType->id,
                'start_date' => '2026-08-01',
                'number_of_days' => 3,
                'discount_type' => 'fixed',
                'discount_value' => 50000,
                'options' => [
                    ['option_type_id' => $optionType->id],
                ],
            ],
        ]);

        $line = $quote->quoteLines->first();

        // 250000/day x 3 days x coefficient 2 = 1,500,000 service amount
        $this->assertSame('250000.00', (string) $line->daily_rate);
        $this->assertSame('2.00', (string) $line->service_coefficient);
        // option: 5% of the 1,500,000 service amount = 75,000
        $this->assertSame('75000.00', (string) $line->options_amount);
        $this->assertSame('50000.00', (string) $line->discount_amount);
        $this->assertSame('1525000.00', (string) $line->line_total);
        $this->assertSame('1525000.00', (string) $quote->fresh()->total);
        $this->assertStringStartsWith('QUO-'.now()->year.'-', $quote->number);
        $this->assertSame(AmountMode::Percentage, $line->quoteLineOptions->first()->mode);
    }

    public function test_it_persists_the_optional_departure_time(): void
    {
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);
        $this->givenRentalRate($vehicle, 799, 1, 5, 250000);
        $serviceType = ServiceType::create(['name' => 'Location', 'coefficient' => 1]);
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $user = User::factory()->create();

        $service = app(QuoteCalculationService::class);

        $quote = $service->createQuote($customer->id, $user->id, [
            [
                'vehicle_id' => $vehicle->id,
                'route_id' => null,
                'distance_km' => 450,
                'service_type_id' => $serviceType->id,
                'start_date' => '2026-08-01',
                'number_of_days' => 3,
                'departure_time' => '06:30',
            ],
        ]);

        $this->assertSame('06:30', $quote->quoteLines->first()->departure_time);
    }

    public function test_the_departure_time_defaults_to_null(): void
    {
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);
        $this->givenRentalRate($vehicle, 799, 1, 5, 250000);
        $serviceType = ServiceType::create(['name' => 'Location', 'coefficient' => 1]);
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $user = User::factory()->create();

        $service = app(QuoteCalculationService::class);

        $quote = $service->createQuote($customer->id, $user->id, [
            [
                'vehicle_id' => $vehicle->id,
                'route_id' => null,
                'distance_km' => 450,
                'service_type_id' => $serviceType->id,
                'start_date' => '2026-08-01',
                'number_of_days' => 3,
            ],
        ]);

        $this->assertNull($quote->quoteLines->first()->departure_time);
    }

    public function test_it_throws_when_no_tariff_matches(): void
    {
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);
        $serviceType = ServiceType::create(['name' => 'Location', 'coefficient' => 1]);
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $user = User::factory()->create();

        $service = app(QuoteCalculationService::class);

        $this->expectException(NoTariffFoundException::class);

        $service->createQuote($customer->id, $user->id, [
            [
                'vehicle_id' => $vehicle->id,
                'route_id' => null,
                'distance_km' => 450,
                'service_type_id' => $serviceType->id,
                'start_date' => '2026-08-01',
                'number_of_days' => 3,
            ],
        ]);
    }
}
