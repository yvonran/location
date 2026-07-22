<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceTypesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_types_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('service_types'));
        $this->assertTrue(Schema::hasColumns('service_types', [
            'id', 'name', 'coefficient', 'description', 'active', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_service_type_defaults_to_active(): void
    {
        $id = DB::table('service_types')->insertGetId([
            'name' => 'Transfert',
            'coefficient' => 2.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('service_types', [
            'id' => $id,
            'active' => 1,
        ]);
    }
}
