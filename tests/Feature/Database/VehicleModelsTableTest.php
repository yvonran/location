<?php

namespace Tests\Feature\Database;

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleModelsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_models_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('vehicle_models'));
        $this->assertTrue(Schema::hasColumns('vehicle_models', [
            'id', 'brand_id', 'vehicle_type_id', 'name', 'is_supported', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_model_is_not_supported_by_default(): void
    {
        $brand = Brand::create(['name' => 'Hyundai']);

        $id = DB::table('vehicle_models')->insertGetId([
            'brand_id' => $brand->id,
            'name' => 'County',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('vehicle_models', ['id' => $id, 'is_supported' => false]);
    }
}
