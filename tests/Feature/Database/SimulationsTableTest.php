<?php

namespace Tests\Feature\Database;

use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SimulationsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulations_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('simulations'));
        $this->assertTrue(Schema::hasColumns('simulations', [
            'id', 'vehicle_id', 'number_of_days', 'departure_time', 'distance_km',
            'daily_rate', 'meal_included', 'fuel_included', 'meal_cost', 'fuel_cost',
            'vehicle_amount', 'total', 'created_at', 'updated_at',
        ]));
    }

    public function test_simulation_legs_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('simulation_legs'));
        $this->assertTrue(Schema::hasColumns('simulation_legs', [
            'id', 'simulation_id', 'position', 'from_point', 'to_point', 'distance_km',
            'created_at', 'updated_at',
        ]));
    }

    public function test_deleting_a_simulation_cascades_to_its_legs(): void
    {
        $vehicle = Vehicle::factory()->create();

        $simulationId = DB::table('simulations')->insertGetId([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'distance_km' => 450,
            'daily_rate' => 250000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $legId = DB::table('simulation_legs')->insertGetId([
            'simulation_id' => $simulationId,
            'position' => 0,
            'from_point' => 'Antananarivo',
            'to_point' => 'Toamasina',
            'distance_km' => 367,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('simulations')->where('id', $simulationId)->delete();

        $this->assertDatabaseMissing('simulation_legs', ['id' => $legId]);
    }

    public function test_deleting_the_vehicle_is_restricted_while_simulations_reference_it(): void
    {
        $vehicle = Vehicle::factory()->create();

        DB::table('simulations')->insert([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'distance_km' => 450,
            'daily_rate' => 250000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('vehicles')->where('id', $vehicle->id)->delete();
    }
}
