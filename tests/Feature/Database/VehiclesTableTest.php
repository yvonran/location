<?php

namespace Tests\Feature\Database;

use App\Models\VehicleModel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehiclesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicles_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('vehicles'));
        $this->assertTrue(Schema::hasColumns('vehicles', [
            'id', 'name', 'vehicle_model_id', 'seats', 'registration_number',
            'year', 'has_air_conditioning', 'status',
            'created_at', 'updated_at', 'deleted_at',
        ]));
    }

    public function test_a_vehicle_defaults_to_available_status(): void
    {
        $id = DB::table('vehicles')->insertGetId([
            'name' => 'Starex 1',
            'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8,
            'registration_number' => '1234 TBA',
            'year' => 2020,
            'has_air_conditioning' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $id,
            'status' => 'available',
        ]);
    }

    public function test_registration_number_must_be_unique(): void
    {
        DB::table('vehicles')->insert([
            'name' => 'Starex 1',
            'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8,
            'registration_number' => '1234 TBA',
            'year' => 2020,
            'has_air_conditioning' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('vehicles')->insert([
            'name' => 'Starex 2',
            'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8,
            'registration_number' => '1234 TBA',
            'year' => 2021,
            'has_air_conditioning' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
