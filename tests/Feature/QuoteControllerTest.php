<?php

namespace Tests\Feature;

use App\Enums\AmountMode;
use App\Models\Customer;
use App\Models\OptionType;
use App\Models\Quote;
use App\Models\ServiceType;
use App\Models\Tariff;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('quotes.create'))->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_can_view_the_create_page(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('quotes.create'))
            ->assertOk();
    }

    public function test_an_authenticated_user_can_create_a_quote_in_one_request(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);
        Tariff::create([
            'vehicle_id' => $vehicle->id, 'min_distance_km' => 0, 'max_distance_km' => 799,
            'min_days' => 1, 'max_days' => 5, 'daily_rate' => 250000,
        ]);
        $serviceType = ServiceType::create(['name' => 'Location', 'coefficient' => 1]);
        $optionType = OptionType::create([
            'name' => 'Assurance', 'default_mode' => AmountMode::Percentage, 'default_value' => 5,
        ]);

        $response = $this->actingAs($user)->post(route('quotes.store'), [
            'customer_id' => $customer->id,
            'lines' => [
                [
                    'vehicle_id' => $vehicle->id,
                    'distance_km' => 450,
                    'service_type_id' => $serviceType->id,
                    'start_date' => '2026-08-01',
                    'number_of_days' => 3,
                    'options' => [
                        ['option_type_id' => $optionType->id],
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('quotes', ['customer_id' => $customer->id, 'user_id' => $user->id]);
        $this->assertDatabaseCount('quote_lines', 1);
        $this->assertDatabaseCount('quote_line_options', 1);

        $quote = Quote::firstOrFail();
        $response->assertRedirect(route('quotes.show', $quote));
    }

    public function test_a_malformed_departure_time_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);
        Tariff::create([
            'vehicle_id' => $vehicle->id, 'min_distance_km' => 0, 'max_distance_km' => 799,
            'min_days' => 1, 'max_days' => 5, 'daily_rate' => 250000,
        ]);
        $serviceType = ServiceType::create(['name' => 'Location', 'coefficient' => 1]);

        $this->actingAs($user)->post(route('quotes.store'), [
            'customer_id' => $customer->id,
            'lines' => [
                [
                    'vehicle_id' => $vehicle->id,
                    'distance_km' => 450,
                    'service_type_id' => $serviceType->id,
                    'start_date' => '2026-08-01',
                    'number_of_days' => 3,
                    'departure_time' => 'not-a-time',
                ],
            ],
        ])->assertSessionHasErrors('lines.0.departure_time');

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_it_returns_a_validation_error_when_no_tariff_matches(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);
        $serviceType = ServiceType::create(['name' => 'Location', 'coefficient' => 1]);

        $response = $this->actingAs($user)->post(route('quotes.store'), [
            'customer_id' => $customer->id,
            'lines' => [
                [
                    'vehicle_id' => $vehicle->id,
                    'distance_km' => 450,
                    'service_type_id' => $serviceType->id,
                    'start_date' => '2026-08-01',
                    'number_of_days' => 3,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('lines');
        $this->assertDatabaseCount('quotes', 0);
    }
}
