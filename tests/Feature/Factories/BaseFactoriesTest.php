<?php

namespace Tests\Feature\Factories;

use App\Models\Customer;
use App\Models\OptionType;
use App\Models\Route;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_factory_creates_a_valid_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_vehicle_factory_can_create_many_without_unique_collisions(): void
    {
        $vehicles = Vehicle::factory()->count(5)->create();

        $this->assertCount(5, $vehicles);
        $this->assertCount(5, $vehicles->pluck('registration_number')->unique());
    }

    public function test_route_factory_creates_a_valid_route(): void
    {
        $route = Route::factory()->create();

        $this->assertDatabaseHas('routes', ['id' => $route->id]);
    }

    public function test_service_type_factory_creates_a_valid_service_type(): void
    {
        $serviceType = ServiceType::factory()->create();

        $this->assertDatabaseHas('service_types', ['id' => $serviceType->id]);
    }

    public function test_option_type_factory_creates_a_valid_option_type(): void
    {
        $optionType = OptionType::factory()->create();

        $this->assertDatabaseHas('option_types', ['id' => $optionType->id]);
    }
}
