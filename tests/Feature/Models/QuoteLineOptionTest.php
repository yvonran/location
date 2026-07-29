<?php

namespace Tests\Feature\Models;

use App\Enums\AmountMode;
use App\Models\Customer;
use App\Models\OptionType;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\QuoteLineOption;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteLineOptionTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuoteLine(): QuoteLine
    {
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $user = User::factory()->create();
        $quote = Quote::create([
            'number' => 'QUO-2026-0001', 'customer_id' => $customer->id,
            'user_id' => $user->id, 'quote_date' => '2026-07-22',
        ]);
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);
        $serviceType = ServiceType::create(['name' => 'Location', 'coefficient' => 1]);

        return QuoteLine::create([
            'quote_id' => $quote->id, 'vehicle_id' => $vehicle->id,
            'service_type_id' => $serviceType->id, 'start_date' => '2026-08-01',
            'number_of_days' => 3, 'distance_km' => 450.5,
            'daily_rate' => 250000, 'service_coefficient' => 1,
        ]);
    }

    public function test_an_option_belongs_to_a_quote_line_and_an_option_type_who_both_list_it(): void
    {
        $line = $this->makeQuoteLine();
        $optionType = OptionType::create([
            'name' => 'Assurance', 'default_mode' => AmountMode::Percentage, 'default_value' => 10,
        ]);

        $option = QuoteLineOption::create([
            'quote_line_id' => $line->id,
            'option_type_id' => $optionType->id,
            'mode' => AmountMode::Percentage,
            'value' => 10,
            'amount' => 75000,
        ]);

        $this->assertTrue($option->quoteLine->is($line));
        $this->assertTrue($option->optionType->is($optionType));
        $this->assertTrue($line->quoteLineOptions->contains($option));
        $this->assertTrue($optionType->quoteLineOptions->contains($option));
        $this->assertSame(AmountMode::Percentage, $option->fresh()->mode);
        $this->assertSame('75000.00', $option->fresh()->amount);
    }
}
