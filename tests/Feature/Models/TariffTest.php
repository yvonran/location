<?php

namespace Tests\Feature\Models;

use App\Models\Tariff;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TariffTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tariff_belongs_to_a_vehicle_and_the_vehicle_lists_its_tariffs(): void
    {
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);

        $tariff = Tariff::create([
            'vehicle_id' => $vehicle->id,
            'min_distance_km' => 0,
            'max_distance_km' => 799,
            'min_days' => 1,
            'max_days' => 5,
            'daily_rate' => 250000,
        ]);

        $this->assertTrue($tariff->vehicle->is($vehicle));
        $this->assertTrue($vehicle->tariffs->contains($tariff));
        $this->assertSame('250000.00', $tariff->fresh()->daily_rate);
    }
}
