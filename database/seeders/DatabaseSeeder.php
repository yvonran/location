<?php

namespace Database\Seeders;

use App\Enums\AmountMode;
use App\Enums\RentalZone;
use App\Models\Customer;
use App\Models\OptionType;
use App\Models\RentalCondition;
use App\Models\Route;
use App\Models\ServiceType;
use App\Models\Tariff;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $starex = Vehicle::create([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);

        $landCruiser = Vehicle::create([
            'name' => 'Land Cruiser 1', 'brand' => 'Toyota', 'model' => 'Land Cruiser',
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

        foreach ([$starex, $landCruiser] as $vehicle) {
            $condition = RentalCondition::create(['vehicle_id' => $vehicle->id]);
            $premium = $vehicle->is($landCruiser) ? 50000 : 0;

            foreach ([
                [RentalZone::City, 1, 5, 180000],
                [RentalZone::City, 6, null, 160000],
                [RentalZone::Suburb, 1, 5, 220000],
                [RentalZone::Suburb, 6, null, 200000],
                [RentalZone::LongDistance, 1, 5, 250000],
                [RentalZone::LongDistance, 6, 10, 220000],
                [RentalZone::LongDistance, 11, null, 200000],
                [RentalZone::VeryLongDistance, 1, 5, 350000],
                [RentalZone::VeryLongDistance, 6, 10, 310000],
                [RentalZone::VeryLongDistance, 11, null, 280000],
            ] as [$zone, $minDays, $maxDays, $dailyRate]) {
                $condition->rentalRates()->create([
                    'zone' => $zone,
                    'min_days' => $minDays,
                    'max_days' => $maxDays,
                    'daily_rate' => $dailyRate + $premium,
                ]);
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
            'name' => 'Hery Rakotondrabe', 'phone' => '0341112233',
            'email' => 'hery.rakoto@example.mg', 'address' => 'Analakely, Antananarivo',
            'tax_id' => 'NIF0012345',
        ]);

        Customer::create([
            'name' => 'Voahangy Rasoanaivo', 'phone' => '0331234567',
            'email' => 'voahangy.rasoanaivo@example.mg', 'address' => 'Isotry, Antananarivo',
        ]);
    }
}
