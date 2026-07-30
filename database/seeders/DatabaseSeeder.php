<?php

namespace Database\Seeders;

use App\Enums\AmountMode;
use App\Models\Customer;
use App\Models\OptionType;
use App\Models\RentalCondition;
use App\Models\Route;
use App\Models\ServiceType;
use App\Models\Tariff;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Support\Roles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

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

        $starex = Vehicle::create([
            'user_id' => $owner->id,
            'name' => 'Starex 1',
            'vehicle_model_id' => $this->modelId('Hyundai', 'Starex'),
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);

        $landCruiser = Vehicle::create([
            'user_id' => $owner->id,
            'name' => 'Land Cruiser 1',
            'vehicle_model_id' => $this->modelId('Toyota', 'Land Cruiser'),
            'seats' => 7, 'registration_number' => '5678 TBB', 'year' => 2019,
            'has_air_conditioning' => true,
        ]);

        foreach ([
            [$starex->id, 0, 799, 1, 5, 250000],
            [$starex->id, 0, 799, 6, 10, 220000],
            [$starex->id, 0, 799, 11, null, 200000],
            [$starex->id, 800, null, 1, 5, 350000],
            [$starex->id, 800, null, 6, 10, 310000],
            [$starex->id, 800, null, 11, null, 250000],
            [$landCruiser->id, 0, 799, 1, 5, 300000],
            [$landCruiser->id, 0, 799, 6, 10, 270000],
            [$landCruiser->id, 0, 799, 11, null, 240000],
            [$landCruiser->id, 800, null, 1, 5, 400000],
            [$landCruiser->id, 800, null, 6, 10, 360000],
            [$landCruiser->id, 800, null, 11, null, 300000],
        ] as [$vehicleId, $minDistance, $maxDistance, $minDays, $maxDays, $dailyRate]) {
            Tariff::create([
                'vehicle_id' => $vehicleId,
                'min_distance_km' => $minDistance,
                'max_distance_km' => $maxDistance,
                'min_days' => $minDays,
                'max_days' => $maxDays,
                'daily_rate' => $dailyRate,
            ]);
        }

        // Découpage de démonstration : des zones libres, propres à chaque véhicule.
        foreach ([
            [$starex, [
                ['Ville', 50, [[1, 5, 180000], [6, null, 160000]]],
                ['Périphérie', 100, [[1, 5, 220000], [6, null, 200000]]],
                ['Longue distance', 700, [[1, 5, 250000], [6, 10, 220000], [11, null, 200000]]],
                ['Très longue distance', null, [[1, 5, 350000], [6, 10, 310000], [11, null, 280000]]],
            ]],
            [$landCruiser, [
                ['Antananarivo intra-muros', 40, [[1, null, 230000]]],
                ['Grand Tana', 120, [[1, 5, 270000], [6, null, 250000]]],
                ['Province', 800, [[1, 5, 300000], [6, 10, 280000], [11, null, 260000]]],
                ['Grand Sud', null, [[1, 10, 420000], [11, null, 380000]]],
            ]],
        ] as [$vehicle, $zones]) {
            $condition = RentalCondition::create(['vehicle_id' => $vehicle->id]);

            foreach ($zones as $position => [$name, $maxKm, $rates]) {
                $zone = $condition->rentalZones()->create([
                    'name' => $name,
                    'max_km' => $maxKm,
                    'position' => $position,
                ]);

                foreach ($rates as [$minDays, $maxDays, $dailyRate]) {
                    $zone->rentalRates()->create([
                        'min_days' => $minDays,
                        'max_days' => $maxDays,
                        'daily_rate' => $dailyRate,
                    ]);
                }
            }
        }

        foreach ([
            ['name' => 'RN1', 'departure_city' => 'Antananarivo', 'arrival_city' => 'Analavory', 'distance_km' => 110, 'description' => 'Antananarivo → Arivonimamo → Miarinarivo → Analavory'],
            ['name' => 'RN2', 'departure_city' => 'Antananarivo', 'arrival_city' => 'Toamasina', 'distance_km' => 367, 'description' => 'Antananarivo → Moramanga → Brickaville → Toamasina'],
            ['name' => 'RN7', 'departure_city' => 'Antananarivo', 'arrival_city' => 'Toliara', 'distance_km' => 956, 'description' => 'Antananarivo → Antsirabe → Ambositra → Fianarantsoa → Toliara'],
        ] as $route) {
            Route::create($route);
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
    }

    private function modelId(string $brand, string $model): int
    {
        return VehicleModel::query()
            ->whereRelation('brand', 'name', $brand)
            ->where('name', $model)
            ->firstOrFail()
            ->id;
    }
}
