<?php

namespace Tests\Feature\Database;

use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RentalConditionsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_tables_have_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('rental_conditions'));
        $this->assertTrue(Schema::hasColumns('rental_conditions', [
            'id', 'vehicle_id', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('rental_zones'));
        $this->assertTrue(Schema::hasColumns('rental_zones', [
            'id', 'rental_condition_id', 'name', 'max_km', 'position', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('rental_rates'));
        $this->assertTrue(Schema::hasColumns('rental_rates', [
            'id', 'rental_zone_id', 'min_days', 'max_days', 'daily_rate', 'created_at', 'updated_at',
        ]));
    }

    public function test_the_fixed_zone_columns_are_gone(): void
    {
        $this->assertFalse(Schema::hasColumn('rental_conditions', 'city_max_km'));
        $this->assertFalse(Schema::hasColumn('rental_conditions', 'suburb_max_km'));
        $this->assertFalse(Schema::hasColumn('rental_conditions', 'long_distance_max_km'));
        $this->assertFalse(Schema::hasColumn('rental_rates', 'zone'));
    }

    public function test_a_vehicle_cannot_have_two_sets_of_conditions(): void
    {
        $vehicle = Vehicle::factory()->create();

        DB::table('rental_conditions')->insert([
            'vehicle_id' => $vehicle->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('rental_conditions')->insert([
            'vehicle_id' => $vehicle->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_deleting_the_vehicle_cascades_down_to_the_rates(): void
    {
        $vehicle = Vehicle::factory()->create();

        $conditionId = DB::table('rental_conditions')->insertGetId([
            'vehicle_id' => $vehicle->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $zoneId = DB::table('rental_zones')->insertGetId([
            'rental_condition_id' => $conditionId, 'name' => 'Ville', 'max_km' => 50,
            'position' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $rateId = DB::table('rental_rates')->insertGetId([
            'rental_zone_id' => $zoneId, 'min_days' => 1, 'max_days' => null,
            'daily_rate' => 180000, 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('vehicles')->where('id', $vehicle->id)->delete();

        $this->assertDatabaseMissing('rental_conditions', ['id' => $conditionId]);
        $this->assertDatabaseMissing('rental_zones', ['id' => $zoneId]);
        $this->assertDatabaseMissing('rental_rates', ['id' => $rateId]);
    }
}
