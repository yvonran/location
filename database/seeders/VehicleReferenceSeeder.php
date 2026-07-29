<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class VehicleReferenceSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Modèles absents de la liste importée mais utilisés par l'agence, avec
     * leur type. Format : marque => [type => [modèles]].
     */
    private const EXTRA_MODELS = [
        'Hyundai' => [
            'Bus' => ['County'],
            'Minibus' => ['Starex'],
        ],
        'Nissan' => [
            'Bus' => ['Urvan'],
            'Minibus' => ['Civilian'],
        ],
    ];

    /**
     * Rattachement des modèles déjà importés à un type. Format : type => [marque => [modèles]].
     */
    private const TYPE_ASSIGNMENTS = [
        'Bus' => [
            'Mercedes-Benz' => ['Sprinter'],
            'Volkswagen' => ['Crafter', 'Crafter Van', 'Crafter Kombi'],
        ],
        'Minibus' => [
            'Volkswagen' => ['T5 Transporter Shuttle', 'Caravelle', 'Multivan'],
            'Toyota' => ['Hiace', 'Hiace Van'],
        ],
        '4x4' => [
            'Hyundai' => ['Terracan', 'Galloper'],
            'Toyota' => ['Land Cruiser', 'Hilux', '4-Runner'],
            'Mitsubishi' => ['Pajero', 'Pajero Sport', 'L200'],
        ],
        'SUV' => [
            'Kia' => ['Sorento', 'Sportage'],
            'Dacia' => ['Duster'],
            'Hyundai' => ['Santa Fe', 'Tucson'],
            'Toyota' => ['RAV4'],
        ],
        'Plaisir' => [
            'Hyundai' => ['Getz', 'i10', 'i20', 'Accent'],
            'Volkswagen' => ['Golf', 'Polo'],
            'Toyota' => ['Yaris', 'Corolla'],
        ],
    ];

    public function run(): void
    {
        $types = $this->seedTypes();
        $brands = $this->seedBrandsAndModels();

        $this->assignTypes($types, $brands);
        $this->seedExtraModels($types, $brands);
    }

    /**
     * @return array<string, VehicleType>
     */
    private function seedTypes(): array
    {
        $types = [];

        foreach (VehicleType::DEFAULTS as $position => $name) {
            $types[$name] = VehicleType::firstOrCreate(['name' => $name], ['position' => $position]);
        }

        return $types;
    }

    /**
     * @return array<string, Brand>
     */
    private function seedBrandsAndModels(): array
    {
        $path = database_path('data/car-list.json');

        if (! is_file($path)) {
            throw new RuntimeException("Liste des véhicules introuvable : {$path}");
        }

        $entries = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $brands = [];

        foreach ($entries as $entry) {
            $brand = Brand::firstOrCreate(['name' => $entry['brand']]);
            $brands[$entry['brand']] = $brand;

            $now = now();
            $rows = [];

            foreach (array_unique($entry['models']) as $modelName) {
                $rows[] = [
                    'brand_id' => $brand->id,
                    'vehicle_type_id' => null,
                    'name' => $modelName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // insertOrIgnore : le seeder doit pouvoir être rejoué sans doublon.
            VehicleModel::insertOrIgnore($rows);
        }

        return $brands;
    }

    /**
     * @param  array<string, VehicleType>  $types
     * @param  array<string, Brand>  $brands
     */
    private function assignTypes(array $types, array $brands): void
    {
        foreach (self::TYPE_ASSIGNMENTS as $typeName => $brandModels) {
            foreach ($brandModels as $brandName => $modelNames) {
                if (! isset($brands[$brandName], $types[$typeName])) {
                    continue;
                }

                VehicleModel::where('brand_id', $brands[$brandName]->id)
                    ->whereIn('name', $modelNames)
                    ->update(['vehicle_type_id' => $types[$typeName]->id]);
            }
        }
    }

    /**
     * @param  array<string, VehicleType>  $types
     * @param  array<string, Brand>  $brands
     */
    private function seedExtraModels(array $types, array $brands): void
    {
        foreach (self::EXTRA_MODELS as $brandName => $typeModels) {
            $brand = $brands[$brandName] ?? Brand::firstOrCreate(['name' => $brandName]);

            foreach ($typeModels as $typeName => $modelNames) {
                foreach ($modelNames as $modelName) {
                    VehicleModel::updateOrCreate(
                        ['brand_id' => $brand->id, 'name' => $modelName],
                        ['vehicle_type_id' => $types[$typeName]->id],
                    );
                }
            }
        }
    }
}
