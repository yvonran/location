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
            'id', 'vehicle_id', 'city_max_km', 'suburb_max_km',
            'long_distance_max_km', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('rental_rates'));
        $this->assertTrue(Schema::hasColumns('rental_rates', [
            'id', 'rental_condition_id', 'zone', 'min_days', 'max_days',
            'daily_rate', 'created_at', 'updated_at',
        ]));
    }

    public function test_the_distance_thresholds_default_to_50_100_and_700_km(): void
    {
        $vehicle = Vehicle::factory()->create();

        DB::table('rental_conditions')->insert([
            'vehicle_id' => $vehicle->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('rental_conditions', [
            'vehicle_id' => $vehicle->id,
            'city_max_km' => 50,
            'suburb_max_km' => 100,
            'long_distance_max_km' => 700,
        ]);
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

    public function test_deleting_the_vehicle_cascades_to_conditions_and_rates(): void
    {
        $vehicle = Vehicle::factory()->create();

        $conditionId = DB::table('rental_conditions')->insertGetId([
            'vehicle_id' => $vehicle->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $rateId = DB::table('rental_rates')->insertGetId([
            'rental_condition_id' => $conditionId, 'zone' => 'city',
            'min_days' => 1, 'max_days' => null, 'daily_rate' => 180000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('vehicles')->where('id', $vehicle->id)->delete();

        $this->assertDatabaseMissing('rental_conditions', ['id' => $conditionId]);
        $this->assertDatabaseMissing('rental_rates', ['id' => $rateId]);
    }
}
