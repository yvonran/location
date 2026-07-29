<?php

namespace Tests\Feature\Database;

use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use Database\Seeders\VehicleReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleReferenceSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(VehicleReferenceSeeder::class);
    }

    public function test_the_whole_car_list_is_imported(): void
    {
        $this->assertSame(39, Brand::count());
        $this->assertGreaterThan(800, VehicleModel::count());
    }

    public function test_accented_names_survive_the_import(): void
    {
        // Le fichier source circulait en mojibake : on vérifie qu'il est sain.
        $this->assertDatabaseHas('brands', ['name' => 'Škoda']);
        $this->assertDatabaseHas('brands', ['name' => 'Citroën']);
        $this->assertDatabaseHas('vehicle_models', ['name' => 'Mégane']);
        $this->assertDatabaseHas('vehicle_models', ['name' => 'Scénic']);
        $this->assertDatabaseMissing('brands', ['name' => 'CitroÃ«n']);
    }

    public function test_the_five_default_types_exist_in_order(): void
    {
        $this->assertSame(
            ['Bus', 'Minibus', '4x4', 'SUV', 'Plaisir'],
            VehicleType::orderBy('position')->pluck('name')->all(),
        );
    }

    public function test_the_requested_models_are_classified(): void
    {
        $expected = [
            'Bus' => [['Hyundai', 'County'], ['Nissan', 'Urvan'], ['Mercedes-Benz', 'Sprinter'], ['Volkswagen', 'Crafter']],
            'Minibus' => [['Hyundai', 'Starex'], ['Volkswagen', 'T5 Transporter Shuttle']],
            '4x4' => [['Hyundai', 'Terracan'], ['Hyundai', 'Galloper']],
            'SUV' => [['Kia', 'Sorento'], ['Dacia', 'Duster']],
            'Plaisir' => [['Hyundai', 'Getz'], ['Volkswagen', 'Golf']],
        ];

        foreach ($expected as $typeName => $models) {
            foreach ($models as [$brandName, $modelName]) {
                $model = VehicleModel::query()
                    ->whereRelation('brand', 'name', $brandName)
                    ->where('name', $modelName)
                    ->first();

                $this->assertNotNull($model, "{$brandName} {$modelName} manquant");
                $this->assertSame(
                    $typeName,
                    $model->vehicleType?->name,
                    "{$brandName} {$modelName} devrait être classé en {$typeName}",
                );
            }
        }
    }

    public function test_a_model_name_may_be_shared_by_two_brands(): void
    {
        $this->assertSame(
            2,
            VehicleModel::where('name', 'Matiz')->count(),
            'Matiz existe chez Chevrolet et Daewoo',
        );
    }

    public function test_the_seeder_can_run_twice_without_duplicating(): void
    {
        $brands = Brand::count();
        $models = VehicleModel::count();

        $this->seed(VehicleReferenceSeeder::class);

        $this->assertSame($brands, Brand::count());
        $this->assertSame($models, VehicleModel::count());
    }
}
