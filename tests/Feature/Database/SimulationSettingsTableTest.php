<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SimulationSettingsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulation_settings_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('simulation_settings'));
        $this->assertTrue(Schema::hasColumns('simulation_settings', [
            'id', 'fuel_price_per_liter', 'client_meal_price', 'created_at', 'updated_at',
        ]));
    }
}
