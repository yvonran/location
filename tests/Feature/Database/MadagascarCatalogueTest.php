<?php

namespace Tests\Feature\Database;

use App\Models\Brand;
use App\Models\VehicleModel;
use Database\Seeders\VehicleReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

/**
 * Le catalogue est saisi à la main : un nom mal orthographié serait ignoré en
 * silence et le modèle n'apparaîtrait jamais dans le formulaire véhicule.
 */
class MadagascarCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(VehicleReferenceSeeder::class);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function requestedVehicles(): array
    {
        return [
            'Nissan Urvan' => ['Nissan', 'Urvan', 'Bus'],
            'Land Cruiser' => ['Toyota', 'Land Cruiser', '4x4'],
            'Starex SVX' => ['Hyundai', 'Starex SVX', 'Minibus'],
            'Starex GRX' => ['Hyundai', 'Starex GRX', 'Minibus'],
            'Grand Starex' => ['Hyundai', 'Grand Starex', 'Minibus'],
            'Getz' => ['Hyundai', 'Getz', 'Plaisir'],
            'Sprinter' => ['Mercedes-Benz', 'Sprinter', 'Bus'],
        ];
    }

    #[DataProvider('requestedVehicles')]
    public function test_the_explicitly_requested_vehicles_are_available(
        string $brand,
        string $model,
        string $type,
    ): void {
        $found = VehicleModel::whereRelation('brand', 'name', $brand)
            ->where('name', $model)
            ->first();

        $this->assertNotNull($found, "{$brand} {$model} est absent du référentiel");
        $this->assertTrue((bool) $found->is_supported, "{$brand} {$model} n'est pas disponible");
        $this->assertSame($type, $found->vehicleType?->name);
    }

    public function test_the_common_malagasy_brands_all_offer_something(): void
    {
        foreach (['Renault', 'Hyundai', 'Toyota', 'Mercedes-Benz', 'Nissan'] as $brandName) {
            $count = VehicleModel::whereRelation('brand', 'name', $brandName)
                ->where('is_supported', true)
                ->whereNotNull('vehicle_type_id')
                ->count();

            $this->assertGreaterThan(0, $count, "Aucun modèle disponible pour {$brandName}");
        }
    }

    public function test_every_catalogued_model_actually_exists(): void
    {
        foreach ($this->catalogue() as [$brandName, $modelName, $typeName]) {
            $brand = Brand::where('name', $brandName)->first();
            $this->assertNotNull($brand, "Marque inconnue dans le catalogue : {$brandName}");

            $model = VehicleModel::where('brand_id', $brand->id)->where('name', $modelName)->first();

            $this->assertNotNull(
                $model,
                "Le catalogue référence « {$brandName} {$modelName} », absent de la liste importée",
            );
            $this->assertSame($typeName, $model->vehicleType?->name);
            $this->assertTrue((bool) $model->is_supported);
        }
    }

    public function test_no_model_is_claimed_by_two_types(): void
    {
        $seen = [];

        foreach ($this->catalogue() as [$brandName, $modelName, $typeName]) {
            $key = "{$brandName} {$modelName}";

            $this->assertArrayNotHasKey(
                $key,
                $seen,
                "{$key} est déclaré en {$typeName} et en ".($seen[$key] ?? '?'),
            );

            $seen[$key] = $typeName;
        }
    }

    public function test_unclassified_models_stay_out_of_the_vehicle_form(): void
    {
        // Les ~770 modèles importés sans type ne doivent pas polluer la liste.
        $this->assertSame(
            0,
            VehicleModel::whereNull('vehicle_type_id')->where('is_supported', true)->count(),
        );
        $this->assertGreaterThan(500, VehicleModel::where('is_supported', false)->count());
    }

    /**
     * Aplatit les deux constantes du seeder en [marque, modèle, type].
     *
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    private function catalogue(): array
    {
        $reflection = new ReflectionClass(VehicleReferenceSeeder::class);
        $flat = [];

        foreach ($reflection->getConstant('TYPE_ASSIGNMENTS') as $typeName => $brandModels) {
            foreach ($brandModels as $brandName => $modelNames) {
                foreach ($modelNames as $modelName) {
                    $flat[] = [$brandName, $modelName, $typeName];
                }
            }
        }

        foreach ($reflection->getConstant('EXTRA_MODELS') as $brandName => $typeModels) {
            foreach ($typeModels as $typeName => $modelNames) {
                foreach ($modelNames as $modelName) {
                    $flat[] = [$brandName, $modelName, $typeName];
                }
            }
        }

        return $flat;
    }
}
