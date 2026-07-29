<?php

namespace Tests\Feature\Models;

use App\Models\SimulationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_sensible_defaults_when_none_exist(): void
    {
        $this->assertDatabaseCount('simulation_settings', 0);

        $setting = SimulationSetting::current();

        $this->assertDatabaseCount('simulation_settings', 1);
        $this->assertGreaterThan(0, (float) $setting->fuel_price_per_liter);
        $this->assertGreaterThan(0, (float) $setting->client_meal_price);
    }

    public function test_it_returns_the_existing_row_instead_of_duplicating_it(): void
    {
        SimulationSetting::current()->update(['fuel_price_per_liter' => 6000, 'client_meal_price' => 8000]);

        $setting = SimulationSetting::current();

        $this->assertDatabaseCount('simulation_settings', 1);
        $this->assertSame('6000.00', (string) $setting->fuel_price_per_liter);
        $this->assertSame('8000.00', (string) $setting->client_meal_price);
    }
}
