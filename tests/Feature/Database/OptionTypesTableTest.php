<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OptionTypesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_option_types_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('option_types'));
        $this->assertTrue(Schema::hasColumns('option_types', [
            'id', 'name', 'default_mode', 'default_value', 'active', 'created_at', 'updated_at',
        ]));
    }

    public function test_an_option_type_can_be_created_with_percentage_mode(): void
    {
        $id = DB::table('option_types')->insertGetId([
            'name' => 'Assurance',
            'default_mode' => 'percentage',
            'default_value' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('option_types', [
            'id' => $id,
            'default_mode' => 'percentage',
            'active' => 1,
        ]);
    }
}
