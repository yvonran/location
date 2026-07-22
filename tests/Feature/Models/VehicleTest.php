<?php

namespace Tests\Feature\Models;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_vehicle_can_be_created_with_a_default_available_status(): void
    {
        $vehicle = Vehicle::create([
            'name' => 'Starex 1',
            'brand' => 'Hyundai',
            'model' => 'Starex',
            'seats' => 8,
            'registration_number' => '1234 TBA',
            'year' => 2020,
            'has_air_conditioning' => true,
        ]);

        $this->assertSame(VehicleStatus::Available, $vehicle->fresh()->status);
        $this->assertIsInt($vehicle->fresh()->seats);
        $this->assertTrue($vehicle->fresh()->has_air_conditioning);
    }

    public function test_deleting_a_vehicle_soft_deletes_it(): void
    {
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);

        $vehicle->delete();

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }
}
