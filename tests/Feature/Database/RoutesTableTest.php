<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoutesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_routes_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('routes'));
        $this->assertTrue(Schema::hasColumns('routes', [
            'id', 'name', 'departure_city', 'arrival_city', 'distance_km',
            'estimated_duration_minutes', 'description', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_route_can_be_created_without_optional_fields(): void
    {
        $id = DB::table('routes')->insertGetId([
            'name' => 'RN2',
            'departure_city' => 'Antananarivo',
            'arrival_city' => 'Toamasina',
            'distance_km' => 367,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('routes', [
            'id' => $id,
            'estimated_duration_minutes' => null,
            'description' => null,
        ]);
    }
}
