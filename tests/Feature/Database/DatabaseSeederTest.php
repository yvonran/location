<?php

namespace Tests\Feature\Database;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
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

        $this->assertDatabaseCount('vehicles', 2);
        $this->assertDatabaseCount('customers', 2);
        $this->assertDatabaseCount('rental_conditions', 2);
    }

    public function test_every_seeded_vehicle_points_to_a_real_model(): void
    {
        $this->seed(DatabaseSeeder::class);

        $vehicles = Vehicle::withoutGlobalScope('owned')->with('vehicleModel.brand')->get();

        $this->assertCount(2, $vehicles);

        foreach ($vehicles as $vehicle) {
            $this->assertNotNull(
                $vehicle->vehicleModel,
                "{$vehicle->name} n'est rattaché à aucun modèle du référentiel",
            );
            $this->assertNotNull($vehicle->vehicleModel->brand);
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
        $this->assertSame(2, Vehicle::withoutGlobalScope('owned')->where('user_id', $owner->id)->count());
    }
}
