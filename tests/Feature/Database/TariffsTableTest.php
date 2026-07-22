<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TariffsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_tariffs_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('tariffs'));
        $this->assertTrue(Schema::hasColumns('tariffs', [
            'id', 'vehicle_id', 'min_distance_km', 'max_distance_km',
            'min_days', 'max_days', 'daily_rate', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_tariff_can_be_created_for_a_vehicle_with_no_max_bounds(): void
    {
        $vehicleId = DB::table('vehicles')->insertGetId([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $id = DB::table('tariffs')->insertGetId([
            'vehicle_id' => $vehicleId,
            'min_distance_km' => 0,
            'max_distance_km' => 799,
            'min_days' => 11,
            'max_days' => null,
            'daily_rate' => 200000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('tariffs', ['id' => $id, 'max_days' => null]);
    }

    public function test_deleting_the_vehicle_cascades_to_its_tariffs(): void
    {
        $vehicleId = DB::table('vehicles')->insertGetId([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $tariffId = DB::table('tariffs')->insertGetId([
            'vehicle_id' => $vehicleId, 'min_distance_km' => 0, 'max_distance_km' => 799,
            'min_days' => 1, 'max_days' => 5, 'daily_rate' => 250000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('vehicles')->where('id', $vehicleId)->delete();

        $this->assertDatabaseMissing('tariffs', ['id' => $tariffId]);
    }
}
