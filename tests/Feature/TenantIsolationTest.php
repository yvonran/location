<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\ServiceType;
use App\Models\Simulation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le cloisonnement est la garantie qu'un compte ne voit jamais les données d'un
 * autre. Ces tests attaquent chaque porte d'entrée : listes, fiches, écriture.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_the_vehicle_list_only_shows_my_own(): void
    {
        $mine = $this->user();
        $other = $this->user();

        Vehicle::factory()->for($mine)->count(2)->create();
        Vehicle::factory()->for($other)->count(5)->create();

        $this->actingAs($mine)
            ->get(route('vehicles.index'))
            ->assertInertia(fn ($page) => $page->has('vehicles.data', 2)
                ->where('vehicles.total', 2));
    }

    public function test_another_accounts_vehicle_is_not_reachable(): void
    {
        $mine = $this->user();
        $theirs = Vehicle::factory()->for($this->user())->create();

        // 404 plutôt que 403 : l'existence même de la fiche n'est pas révélée.
        $this->actingAs($mine)->get(route('vehicles.edit', $theirs))->assertNotFound();
        $this->actingAs($mine)->delete(route('vehicles.destroy', $theirs))->assertNotFound();
        $this->actingAs($mine)->get(route('vehicles.conditions.edit', $theirs))->assertNotFound();
        $this->actingAs($mine)->put(route('vehicles.conditions.update', $theirs), [
            'zones' => [['name' => 'Tout', 'max_km' => null, 'rates' => []]],
        ])->assertNotFound();

        $this->assertNotSoftDeleted($theirs);
    }

    public function test_the_quote_list_and_customers_are_partitioned(): void
    {
        $mine = $this->user();
        $other = $this->user();

        Customer::factory()->for($mine)->create();
        Customer::factory()->for($other)->count(3)->create();

        $this->actingAs($mine)
            ->get(route('quotes.create'))
            ->assertInertia(fn ($page) => $page->has('customers', 1));
    }

    public function test_the_quote_creation_form_only_offers_my_vehicles(): void
    {
        $mine = $this->user();
        Vehicle::factory()->for($mine)->create();
        Vehicle::factory()->for($this->user())->count(4)->create();

        $this->actingAs($mine)
            ->get(route('quotes.create'))
            ->assertInertia(fn ($page) => $page->has('vehicles', 1));
    }

    public function test_simulations_are_partitioned(): void
    {
        $mine = $this->user();
        $other = $this->user();

        Simulation::factory()->for($mine)->create(['vehicle_id' => Vehicle::factory()->for($mine)]);
        $theirs = Simulation::factory()->for($other)
            ->create(['vehicle_id' => Vehicle::factory()->for($other)]);

        $this->actingAs($mine)
            ->get(route('simulations.index'))
            ->assertInertia(fn ($page) => $page->has('simulations.data', 1));

        $this->actingAs($mine)->get(route('simulations.show', $theirs))->assertNotFound();
    }

    public function test_a_simulation_cannot_be_run_on_another_accounts_vehicle(): void
    {
        $theirs = Vehicle::factory()->for($this->user())->create();

        $this->actingAs($this->user())
            ->post(route('simulations.store'), [
                'vehicle_id' => $theirs->id,
                'number_of_days' => 2,
                'same_return_route' => true,
                'legs' => ['outbound' => [
                    ['from_point' => 'A', 'to_point' => 'B', 'distance_km' => 100],
                ]],
            ])
            ->assertSessionHasErrors('vehicle_id');

        $this->assertDatabaseCount('simulations', 0);
    }

    public function test_a_created_record_is_attached_to_its_author(): void
    {
        $mine = $this->user();

        $this->actingAs($mine);
        $vehicle = Vehicle::create([
            'name' => 'Sans propriétaire explicite',
            'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '9999 TBA', 'year' => 2020,
        ]);

        $this->assertSame($mine->id, $vehicle->fresh()->user_id);
    }

    public function test_the_super_admin_sees_every_account(): void
    {
        Role::findOrCreate(Roles::SuperAdmin, 'web');
        $superAdmin = tap($this->user())->assignRole(Roles::SuperAdmin);

        Vehicle::factory()->for($this->user())->count(3)->create();
        Vehicle::factory()->for($this->user())->count(2)->create();

        $this->actingAs($superAdmin)
            ->get(route('vehicles.index'))
            ->assertInertia(fn ($page) => $page->where('vehicles.total', 5));
    }

    public function test_quote_numbering_stays_unique_across_accounts(): void
    {
        $first = $this->user();
        $second = $this->user();

        $quoteOfFirst = $this->quoteFor($first);
        $quoteOfSecond = $this->quoteFor($second);

        // Sans précaution, chaque compte repartirait de QUO-<année>-0001.
        $this->assertNotSame($quoteOfFirst, $quoteOfSecond);
    }

    private function quoteFor(User $user): string
    {
        $this->actingAs($user);

        $vehicle = Vehicle::factory()->for($user)->create();
        $condition = $vehicle->rentalCondition()->create([]);
        $zone = $condition->rentalZones()->create(['name' => 'Zone', 'max_km' => null, 'position' => 0]);
        $zone->rentalRates()->create(['min_days' => 1, 'max_days' => null, 'daily_rate' => 100000]);

        $customer = Customer::factory()->for($user)->create();
        $serviceType = ServiceType::create(['name' => 'Location '.$user->id, 'coefficient' => 1]);

        $this->post(route('quotes.store'), [
            'customer_id' => $customer->id,
            'lines' => [[
                'vehicle_id' => $vehicle->id,
                'distance_km' => 100,
                'service_type_id' => $serviceType->id,
                'start_date' => '2026-08-01',
                'number_of_days' => 2,
                'options' => [],
            ]],
        ])->assertSessionHasNoErrors();

        return Quote::where('user_id', $user->id)->firstOrFail()->number;
    }
}
