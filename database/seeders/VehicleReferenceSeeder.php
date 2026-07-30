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
     * Modèles absents de la liste importée : celle-ci date et ignore aussi bien
     * les véhicules récents que les marques chinoises désormais courantes à
     * Madagascar. Ils sont créés puis rattachés à leur type.
     *
     * Format : marque => [type => [modèles]].
     */
    private const EXTRA_MODELS = [
        'Hyundai' => [
            'Bus' => ['County'],
            'Minibus' => ['Starex SVX', 'Starex GRX', 'Grand Starex', 'Staria'],
            'SUV' => ['Creta', 'Venue', 'Palisade'],
            'Plaisir' => ['Grand i10'],
        ],
        'Nissan' => [
            'Bus' => ['Urvan', 'Civilian'],
            '4x4' => ['Terra'],
        ],
        'Renault' => [
            'SUV' => ['Duster', 'Kiger'],
            'Minibus' => ['Triber'],
            'Plaisir' => ['Kwid', 'Sandero Stepway'],
        ],
        'Toyota' => [
            'Bus' => ['Coaster'],
            'Minibus' => ['Innova', 'Avanza'],
            '4x4' => ['Land Cruiser Prado', 'Fortuner'],
            'SUV' => ['Rush', 'Corolla Cross'],
        ],
        'Mitsubishi' => [
            'Minibus' => ['Xpander'],
            '4x4' => ['Triton'],
        ],
        'Kia' => [
            'SUV' => ['Seltos', 'Sonet'],
        ],
        'Suzuki' => [
            'Minibus' => ['Ertiga'],
            'Plaisir' => ['Dzire', 'S-Presso'],
        ],
        'Ford' => [
            '4x4' => ['Everest'],
        ],
        'Changan' => [
            'SUV' => ['CS15', 'CS35 Plus', 'CS55 Plus', 'CS75 Plus'],
            '4x4' => ['Hunter'],
            'Plaisir' => ['Alsvin', 'Eado'],
        ],
        'Chery' => [
            'SUV' => ['Tiggo 2', 'Tiggo 4 Pro', 'Tiggo 7 Pro', 'Tiggo 8 Pro'],
            'Plaisir' => ['Arrizo 5'],
        ],
        'Haval' => [
            'SUV' => ['Jolion', 'H6'],
            '4x4' => ['Poer'],
        ],
        'MG' => [
            'SUV' => ['ZS', 'HS', 'RX5'],
            'Plaisir' => ['MG3', 'MG5'],
        ],
        'Geely' => [
            'SUV' => ['Coolray', 'Azkarra'],
            'Plaisir' => ['Emgrand'],
        ],
        'JAC' => [
            'Minibus' => ['Sunray'],
            'SUV' => ['S3', 'S7'],
            '4x4' => ['T6', 'T8'],
        ],
        'DFSK' => [
            'Minibus' => ['Super Cab'],
            'SUV' => ['Glory 580'],
        ],
        'Isuzu' => [
            '4x4' => ['D-Max'],
            'SUV' => ['MU-X'],
        ],
        'Mahindra' => [
            '4x4' => ['Scorpio', 'Bolero'],
        ],
    ];

    /**
     * Rattachement des modèles déjà importés à un type. Format : type => [marque => [modèles]].
     */
    private const TYPE_ASSIGNMENTS = [
        'Bus' => [
            'Hyundai' => ['H 350', 'H1 Bus'],
            'Mercedes-Benz' => ['Sprinter'],
            'Volkswagen' => ['Crafter', 'Crafter Van', 'Crafter Kombi'],
            'Ford' => ['Transit', 'Transit Bus'],
        ],
        'Minibus' => [
            'Hyundai' => ['H1', 'H1 Van', 'H200', 'Trajet'],
            'Toyota' => ['Hiace', 'Hiace Van', 'Picnic', 'Avensis Van Verso'],
            'Volkswagen' => ['T5 Transporter Shuttle', 'Caravelle', 'Multivan'],
            'Nissan' => ['Vanette Cargo', 'Serena', 'Primastar', 'Primastar Combi', 'NV200'],
            'Mercedes-Benz' => ['MB 100'],
            'Mitsubishi' => ['L300', 'Grandis'],
            'Renault' => ['Kangoo', 'Kangoo Express', 'Espace', 'Grand Espace'],
        ],
        '4x4' => [
            'Toyota' => ['Land Cruiser', 'Hilux', '4-Runner', 'FJ Cruiser'],
            'Hyundai' => ['Terracan', 'Galloper'],
            'Mitsubishi' => ['Pajero', 'Pajero Sport', 'Pajero Wagon', 'L200', 'L200 Pick up', 'L200 Pick up Allrad'],
            'Nissan' => ['Patrol', 'Patrol GR', 'Terrano', 'Navara', 'NP300 Pickup', 'Pickup', 'King Cab'],
            'Suzuki' => ['Jimny', 'Samurai'],
        ],
        'SUV' => [
            'Toyota' => ['RAV4', 'Highlander', 'Urban Cruiser'],
            'Hyundai' => ['Santa Fe', 'Tucson', 'ix35'],
            'Kia' => ['Sorento', 'Sportage'],
            'Dacia' => ['Duster'],
            'Nissan' => ['X-Trail', 'Qashqai', 'Murano', 'Juke'],
            'Renault' => ['Koleos', 'Captur', 'Kadjar'],
            'Mitsubishi' => ['Outlander', 'ASX'],
            'Suzuki' => ['Grand Vitara', 'Vitara'],
        ],
        'Plaisir' => [
            'Hyundai' => ['Getz', 'i10', 'i20', 'i30', 'Accent', 'Atos', 'Atos Prime', 'Elantra', 'Sonata', 'Matrix'],
            'Toyota' => ['Yaris', 'Corolla', 'Corolla sedan', 'Corolla Combi', 'Avensis', 'Auris', 'Camry', 'Prius', 'Starlet'],
            'Renault' => ['Clio', 'Mégane', 'Scénic', 'Twingo', 'Thalia', 'Fluence', 'Laguna'],
            'Dacia' => ['Logan', 'Logan MCV', 'Sandero'],
            'Nissan' => ['Micra', 'Almera', 'Sunny', 'Note', 'Tiida', 'Primera'],
            'Suzuki' => ['Alto', 'Swift', 'Baleno'],
            'Kia' => ['Picanto', 'Rio', 'Cerato'],
            'Mitsubishi' => ['Lancer', 'Colt'],
            'Volkswagen' => ['Golf', 'Polo'],
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
                    ->update([
                        'vehicle_type_id' => $types[$typeName]->id,
                        'is_supported' => true,
                    ]);
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
                        ['vehicle_type_id' => $types[$typeName]->id, 'is_supported' => true],
                    );
                }
            }
        }
    }
}
