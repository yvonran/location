<?php

namespace Tests\Feature\Database;

use App\Models\Customer;
use App\Models\RentalCondition;
use App\Models\Route;
use App\Models\Simulation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SimulationCalculationService;
use App\Support\Roles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le seeder complet n'était couvert par aucun test : renommer un modèle du
 * référentiel le cassait sans que rien ne le signale.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_full_seeder_runs_end_to_end(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Chaque véhicule doit repartir avec ses conditions de location, sinon
        // aucune simulation ne trouve de tarif.
        $vehicles = Vehicle::withoutGlobalScope('owned')->count();

        $this->assertGreaterThanOrEqual(5, $vehicles);
        $this->assertSame($vehicles, RentalCondition::count());
        $this->assertGreaterThan(0, Customer::withoutGlobalScope('owned')->count());
        $this->assertGreaterThan(20, Route::count());
    }

    public function test_every_seeded_vehicle_points_to_a_real_model(): void
    {
        $this->seed(DatabaseSeeder::class);

        $vehicles = Vehicle::withoutGlobalScope('owned')->with('vehicleModel.brand')->get();

        $this->assertGreaterThanOrEqual(5, $vehicles->count());

        foreach ($vehicles as $vehicle) {
            $this->assertNotNull(
                $vehicle->vehicleModel,
                "{$vehicle->name} n'est rattaché à aucun modèle du référentiel",
            );
            $this->assertNotNull($vehicle->vehicleModel->brand);
        }
    }

    public function test_every_vehicle_can_actually_be_simulated(): void
    {
        $this->seed(DatabaseSeeder::class);

        $service = app(SimulationCalculationService::class);

        // 15 km, 300 km et 1200 km traversent toutes les zones du découpage.
        foreach (Vehicle::withoutGlobalScope('owned')->get() as $vehicle) {
            foreach ([15, 300, 1200] as $km) {
                $result = $service->calculate([
                    'vehicle_id' => $vehicle->id,
                    'number_of_days' => 2,
                    'meal_charged_to_client' => true,
                    'fuel_charged_to_client' => true,
                    'same_return_route' => true,
                    'legs' => ['outbound' => [
                        ['from_point' => 'A', 'to_point' => 'B', 'distance_km' => $km],
                    ]],
                ]);

                $this->assertGreaterThan(0, $result['total'], "{$vehicle->name} à {$km} km");
                $this->assertGreaterThan(0, $result['fuel_cost'], "carburant nul pour {$vehicle->name}");
            }
        }
    }

    public function test_the_demo_simulations_are_ready_to_browse(): void
    {
        $this->seed(DatabaseSeeder::class);

        $simulations = Simulation::withoutGlobalScope('owned')->with('legs')->get();

        $this->assertGreaterThanOrEqual(5, $simulations->count());

        foreach ($simulations as $simulation) {
            $this->assertNotEmpty($simulation->legs);
            $this->assertGreaterThan(0, (float) $simulation->total);
        }
    }

    public function test_the_seeded_data_belongs_to_the_seeded_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertTrue($owner->hasRole(Roles::SuperAdmin));

        // Sans propriétaire, le cloisonnement rendrait ces lignes invisibles.
        $this->assertSame(0, Vehicle::withoutGlobalScope('owned')->whereNull('user_id')->count());
        $this->assertSame(0, Customer::withoutGlobalScope('owned')->whereNull('user_id')->count());
        $this->assertSame(
            Vehicle::withoutGlobalScope('owned')->count(),
            Vehicle::withoutGlobalScope('owned')->where('user_id', $owner->id)->count(),
        );
    }
}
