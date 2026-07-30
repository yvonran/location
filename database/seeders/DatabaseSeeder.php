<?php

namespace Database\Seeders;

use App\Enums\AmountMode;
use App\Models\Customer;
use App\Models\OptionType;
use App\Models\RentalCondition;
use App\Models\Route;
use App\Models\ServiceType;
use App\Models\SimulationSetting;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Services\SimulationCalculationService;
use App\Support\Roles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Bornes hautes du trajet aller, communes à toute la flotte.
     */
    private const ZONES = [
        ['Ville', 50],
        ['Périphérie', 120],
        ['Longue distance', 700],
        ['Grand large', null],
    ];

    /**
     * Flotte de démonstration, un véhicule par type au minimum.
     * Format : nom, marque, modèle, places, immatriculation, année,
     * consommation (L/100 km), puis le tarif journalier de base de chaque zone.
     */
    private const FLEET = [
        ['Sprinter 1', 'Mercedes-Benz', 'Sprinter', 16, '1234 TBA', 2021, 12.5, [350000, 400000, 450000, 550000]],
        ['Coaster 1', 'Toyota', 'Coaster', 25, '2345 TBB', 2019, 15.0, [400000, 450000, 520000, 620000]],
        ['Starex 1', 'Hyundai', 'Starex SVX', 8, '3456 TBC', 2020, 10.0, [180000, 220000, 250000, 350000]],
        ['Hiace 1', 'Toyota', 'Hiace', 14, '4567 TBD', 2018, 11.0, [200000, 240000, 280000, 380000]],
        ['Land Cruiser 1', 'Toyota', 'Land Cruiser', 7, '5678 TBE', 2019, 13.5, [230000, 270000, 300000, 420000]],
        ['Hilux 1', 'Toyota', 'Hilux', 5, '6789 TBF', 2022, 9.5, [200000, 240000, 280000, 380000]],
        ['Duster 1', 'Renault', 'Duster', 5, '7890 TBG', 2023, 8.0, [150000, 180000, 210000, 280000]],
        ['Getz 1', 'Hyundai', 'Getz', 5, '8901 TBH', 2017, 6.5, [100000, 120000, 150000, 200000]],
    ];

    /**
     * Distances routières usuelles au départ d'Antananarivo, plus quelques
     * liaisons de province. Format : nom, départ, arrivée, km, durée estimée.
     */
    private const ROUTES = [
        ['Tana - Ivato', 'Antananarivo', 'Ivato', 15, 30],
        ['Tana - Ambohimanga', 'Antananarivo', 'Ambohimanga', 21, 45],
        ['Tana - Arivonimamo', 'Antananarivo', 'Arivonimamo', 45, 70],
        ['Tana - Ambatolampy', 'Antananarivo', 'Ambatolampy', 68, 100],
        ['Tana - Miarinarivo', 'Antananarivo', 'Miarinarivo', 90, 130],
        ['Tana - Moramanga', 'Antananarivo', 'Moramanga', 110, 150],
        ['Tana - Ampefy', 'Antananarivo', 'Ampefy', 129, 180],
        ['Tana - Andasibe', 'Antananarivo', 'Andasibe', 145, 210],
        ['Tana - Antsirabe', 'Antananarivo', 'Antsirabe', 173, 210],
        ['Tana - Tsiroanomandidy', 'Antananarivo', 'Tsiroanomandidy', 220, 300],
        ['Tana - Ambatondrazaka', 'Antananarivo', 'Ambatondrazaka', 260, 330],
        ['Tana - Ambositra', 'Antananarivo', 'Ambositra', 260, 300],
        ['Tana - Maevatanana', 'Antananarivo', 'Maevatanana', 330, 390],
        ['Tana - Tamatave', 'Antananarivo', 'Toamasina', 367, 420],
        ['Tana - Fianarantsoa', 'Antananarivo', 'Fianarantsoa', 410, 480],
        ['Tana - Ranomafana', 'Antananarivo', 'Ranomafana', 415, 510],
        ['Tana - Ambalavao', 'Antananarivo', 'Ambalavao', 460, 540],
        ['Tana - Mahajanga', 'Antananarivo', 'Mahajanga', 567, 630],
        ['Tana - Ihosy', 'Antananarivo', 'Ihosy', 590, 660],
        ['Tana - Morondava', 'Antananarivo', 'Morondava', 700, 780],
        ['Tana - Antsohihy', 'Antananarivo', 'Antsohihy', 700, 810],
        ['Tana - Ranohira (Isalo)', 'Antananarivo', 'Ranohira', 700, 780],
        ['Tana - Ambanja', 'Antananarivo', 'Ambanja', 850, 960],
        ['Tana - Diego Suarez', 'Antananarivo', 'Antsiranana', 1100, 1260],
        ['Tana - Toliara', 'Antananarivo', 'Toliara', 1200, 1140],
        ['Antsirabe - Fianarantsoa', 'Antsirabe', 'Fianarantsoa', 240, 300],
        ['Fianarantsoa - Manakara', 'Fianarantsoa', 'Manakara', 220, 300],
        ['Tamatave - Soanierana Ivongo', 'Toamasina', 'Soanierana Ivongo', 165, 240],
        ['Toliara - Ifaty', 'Toliara', 'Ifaty', 27, 45],
        ['Morondava - Belo sur Tsiribihina', 'Morondava', 'Belo sur Tsiribihina', 90, 180],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(VehicleReferenceSeeder::class);

        $superAdmin = Role::firstOrCreate(['name' => Roles::SuperAdmin, 'guard_name' => 'web']);

        $owner = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $owner->assignRole($superAdmin);

        // Sans propriétaire, les données de démonstration seraient invisibles :
        // la portée globale filtre sur le compte connecté.
        auth()->login($owner);

        // Prix de référence du calcul automatique (Ar).
        SimulationSetting::query()->firstOrCreate([], [
            'fuel_price_per_liter' => 5900,
            'driver_meal_price' => 8000,
        ]);

        $this->seedFleet($owner);

        foreach (self::ROUTES as [$name, $from, $to, $km, $minutes]) {
            Route::create([
                'name' => $name,
                'departure_city' => $from,
                'arrival_city' => $to,
                'distance_km' => $km,
                'estimated_duration_minutes' => $minutes,
            ]);
        }

        foreach ([
            ['name' => 'Location', 'coefficient' => 1.00],
            ['name' => 'Transfert', 'coefficient' => 2.00],
            ['name' => 'Circuit touristique', 'coefficient' => 1.50],
            ['name' => 'Mise à disposition', 'coefficient' => 1.20],
            ['name' => 'Aller simple', 'coefficient' => 1.00],
            ['name' => 'Aller-retour', 'coefficient' => 1.80],
        ] as $serviceType) {
            ServiceType::create($serviceType);
        }

        foreach ([
            ['name' => 'Chauffeur supplémentaire', 'default_mode' => AmountMode::Fixed, 'default_value' => 50000],
            ['name' => 'Carburant', 'default_mode' => AmountMode::Fixed, 'default_value' => 100000],
            ['name' => 'Péages', 'default_mode' => AmountMode::Fixed, 'default_value' => 20000],
            ['name' => 'Ferry', 'default_mode' => AmountMode::Fixed, 'default_value' => 150000],
            ['name' => 'Hébergement chauffeur', 'default_mode' => AmountMode::Fixed, 'default_value' => 30000],
            ['name' => 'Guide', 'default_mode' => AmountMode::Fixed, 'default_value' => 80000],
            ['name' => 'Assurance', 'default_mode' => AmountMode::Percentage, 'default_value' => 5],
        ] as $optionType) {
            OptionType::create($optionType);
        }

        Customer::create([
            'user_id' => $owner->id,
            'name' => 'Hery Rakotondrabe', 'phone' => '0341112233',
            'email' => 'hery.rakoto@example.mg', 'address' => 'Analakely, Antananarivo',
            'tax_id' => 'NIF0012345',
        ]);

        Customer::create([
            'user_id' => $owner->id,
            'name' => 'Voahangy Rasoanaivo', 'phone' => '0331234567',
            'email' => 'voahangy.rasoanaivo@example.mg', 'address' => 'Isotry, Antananarivo',
        ]);

        $this->seedSimulations();
    }

    /**
     * Chaque véhicule reçoit le même découpage de zones, avec ses propres
     * tarifs. Les paliers de durée appliquent une dégressivité : plein tarif
     * jusqu'à 5 jours, -10 % de 6 à 10, -20 % au-delà.
     */
    private function seedFleet(User $owner): void
    {
        foreach (self::FLEET as [$name, $brand, $model, $seats, $plate, $year, $consumption, $baseRates]) {
            $vehicle = Vehicle::create([
                'user_id' => $owner->id,
                'name' => $name,
                'vehicle_model_id' => $this->modelId($brand, $model),
                'seats' => $seats,
                'registration_number' => $plate,
                'year' => $year,
                'has_air_conditioning' => true,
                'average_consumption' => $consumption,
            ]);

            $condition = RentalCondition::create(['vehicle_id' => $vehicle->id]);

            foreach (self::ZONES as $position => [$zoneName, $maxKm]) {
                $zone = $condition->rentalZones()->create([
                    'name' => $zoneName,
                    'max_km' => $maxKm,
                    'position' => $position,
                ]);

                $base = $baseRates[$position];

                foreach ([[1, 5, 1.0], [6, 10, 0.9], [11, null, 0.8]] as [$minDays, $maxDays, $factor]) {
                    $zone->rentalRates()->create([
                        'min_days' => $minDays,
                        'max_days' => $maxDays,
                        'daily_rate' => round($base * $factor / 1000) * 1000,
                    ]);
                }
            }
        }
    }

    /**
     * Quelques simulations déjà calculées, sur des trajets réels, pour que les
     * écrans ne soient pas vides et que chaque zone soit représentée.
     */
    private function seedSimulations(): void
    {
        $service = app(SimulationCalculationService::class);

        $cases = [
            ['Starex 1', 'Antananarivo', 'Antsirabe', 173, 2, '06:00', true, true],
            ['Sprinter 1', 'Antananarivo', 'Toamasina', 367, 3, '05:30', true, false],
            ['Land Cruiser 1', 'Antananarivo', 'Toliara', 1200, 8, '04:00', true, true],
            ['Duster 1', 'Antananarivo', 'Ampefy', 129, 1, '08:00', false, true],
            ['Getz 1', 'Antananarivo', 'Ivato', 15, 1, '09:00', false, false],
        ];

        foreach ($cases as [$vehicleName, $from, $to, $km, $days, $time, $meal, $fuel]) {
            $vehicle = Vehicle::where('name', $vehicleName)->firstOrFail();

            $service->createSimulation([
                'vehicle_id' => $vehicle->id,
                'number_of_days' => $days,
                'departure_time' => $time,
                'same_return_route' => true,
                'meal_charged_to_client' => $meal,
                'fuel_charged_to_client' => $fuel,
                'legs' => [
                    'outbound' => [
                        ['from_point' => $from, 'to_point' => $to, 'distance_km' => $km],
                    ],
                ],
            ]);
        }
    }

    /**
     * Renvoie l'identifiant d'un modèle du référentiel, ou explique ce qui
     * manque : renommer un modèle dans VehicleReferenceSeeder cassait ce seeder
     * avec une exception muette.
     */
    private function modelId(string $brand, string $model): int
    {
        $id = VehicleModel::query()
            ->whereRelation('brand', 'name', $brand)
            ->where('name', $model)
            ->value('id');

        if ($id !== null) {
            return $id;
        }

        $available = VehicleModel::query()
            ->whereRelation('brand', 'name', $brand)
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ');

        throw new RuntimeException(
            "Modèle « {$brand} {$model} » introuvable dans le référentiel. "
            ."Modèles connus pour {$brand} : ".($available ?: 'aucun').'.'
        );
    }
}
