<?php

namespace Tests\Feature;

use App\Models\Simulation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les URL exposent un identifiant opaque : un identifiant séquentiel laissait
 * deviner les enregistrements voisins et le volume de données.
 */
class PublicUidTest extends TestCase
{
    use RefreshDatabase;

    private ?User $actingUser = null;

    private function user(): User
    {
        return $this->actingUser ??= User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_a_vehicle_url_carries_the_uid_and_not_the_id(): void
    {
        $vehicle = Vehicle::factory()->for($this->user())->create();

        $url = route('vehicles.edit', $vehicle);

        $this->assertStringContainsString($vehicle->uid, $url);
        $this->assertStringNotContainsString("/vehicles/{$vehicle->id}/", $url);
    }

    public function test_a_simulation_url_carries_the_uid(): void
    {
        $simulation = Simulation::factory()->for($this->user())
            ->create(['vehicle_id' => Vehicle::factory()->for($this->user())]);

        $this->assertStringContainsString($simulation->uid, route('simulations.show', $simulation));
    }

    public function test_the_sequential_id_no_longer_resolves(): void
    {
        $vehicle = Vehicle::factory()->for($this->user())->create();

        // L'ancienne adresse /vehicles/1/edit ne doit plus rien renvoyer.
        $this->actingAs($this->user())
            ->get("/vehicles/{$vehicle->id}/edit")
            ->assertNotFound();

        $this->actingAs($this->user())
            ->get("/vehicles/{$vehicle->uid}/edit")
            ->assertOk();
    }

    public function test_guessing_a_neighbouring_uid_is_not_possible(): void
    {
        $mine = Vehicle::factory()->for($this->user())->create();
        Vehicle::factory()->for($this->user())->create();

        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $mine->uid);
    }

    public function test_the_uid_survives_seeders_that_mute_model_events(): void
    {
        // Les seeders utilisent WithoutModelEvents : une génération branchée sur
        // l'événement `creating` laisserait des uid vides, donc des URL cassées.
        Vehicle::withoutEvents(function () {
            Vehicle::factory()->for($this->user())->create(['name' => 'Sans événements']);
        });

        $vehicle = Vehicle::where('name', 'Sans événements')->firstOrFail();

        $this->assertNotEmpty($vehicle->uid);
    }

    public function test_each_record_gets_its_own_uid(): void
    {
        $vehicles = Vehicle::factory()->for($this->user())->count(5)->create();

        $this->assertCount(5, $vehicles->pluck('uid')->unique());
        $this->assertEmpty($vehicles->filter(fn (Vehicle $v) => blank($v->uid)));
    }

    public function test_conditions_routes_also_use_the_uid(): void
    {
        $vehicle = Vehicle::factory()->for($this->user())->create();

        $this->assertStringContainsString($vehicle->uid, route('vehicles.conditions.edit', $vehicle));

        $this->actingAs($this->user())
            ->get("/vehicles/{$vehicle->id}/conditions")
            ->assertNotFound();
    }
}
