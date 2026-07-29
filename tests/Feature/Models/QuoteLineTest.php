<?php

namespace Tests\Feature\Models;

use App\Enums\AmountMode;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Route;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteLineTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuote(): Quote
    {
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $user = User::factory()->create();

        return Quote::create([
            'number' => 'QUO-2026-0001', 'customer_id' => $customer->id,
            'user_id' => $user->id, 'quote_date' => '2026-07-22',
        ]);
    }

    private function makeVehicle(): Vehicle
    {
        return Vehicle::create([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);
    }

    private function makeServiceType(): ServiceType
    {
        return ServiceType::create(['name' => 'Location', 'coefficient' => 1]);
    }

    public function test_a_quote_line_can_be_created_without_a_route_and_all_sides_resolve(): void
    {
        $quote = $this->makeQuote();
        $vehicle = $this->makeVehicle();
        $serviceType = $this->makeServiceType();

        $line = QuoteLine::create([
            'quote_id' => $quote->id,
            'vehicle_id' => $vehicle->id,
            'route_id' => null,
            'service_type_id' => $serviceType->id,
            'start_date' => '2026-08-01',
            'number_of_days' => 3,
            'distance_km' => 450.5,
            'daily_rate' => 250000,
            'service_coefficient' => 1,
        ]);

        $this->assertTrue($line->quote->is($quote));
        $this->assertTrue($line->vehicle->is($vehicle));
        $this->assertTrue($line->serviceType->is($serviceType));
        $this->assertNull($line->route);
        $this->assertTrue($quote->quoteLines->contains($line));
        $this->assertTrue($vehicle->quoteLines->contains($line));
        $this->assertTrue($serviceType->quoteLines->contains($line));
        $this->assertNull($line->fresh()->discount_type);
        $this->assertSame('450.50', $line->fresh()->distance_km);
    }

    public function test_a_quote_line_with_a_route_and_a_discount_type_resolves_both(): void
    {
        $route = Route::create([
            'name' => 'RN2', 'departure_city' => 'Antananarivo',
            'arrival_city' => 'Toamasina', 'distance_km' => 367,
        ]);

        $line = QuoteLine::create([
            'quote_id' => $this->makeQuote()->id,
            'vehicle_id' => $this->makeVehicle()->id,
            'route_id' => $route->id,
            'service_type_id' => $this->makeServiceType()->id,
            'start_date' => '2026-08-01',
            'number_of_days' => 3,
            'distance_km' => $route->distance_km,
            'daily_rate' => 250000,
            'service_coefficient' => 1,
            'discount_type' => AmountMode::Percentage,
            'discount_value' => 10,
        ]);

        $this->assertTrue($line->route->is($route));
        $this->assertTrue($route->quoteLines->contains($line));
        $this->assertSame(AmountMode::Percentage, $line->fresh()->discount_type);
    }
}
