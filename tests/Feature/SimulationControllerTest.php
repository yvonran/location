<?php

namespace Tests\Feature;

use App\Models\RentalCondition;
use App\Models\Simulation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true, 'average_consumption' => 10,
        ]);
    }

    private function givenRentalRate(Vehicle $vehicle, ?int $maxKm, int $minDays, ?int $maxDays, float $dailyRate): void
    {
        $condition = RentalCondition::create(['vehicle_id' => $vehicle->id]);
        $zone = $condition->rentalZones()->create(['name' => 'Zone', 'max_km' => $maxKm, 'position' => 0]);
        $zone->rentalRates()->create(['min_days' => $minDays, 'max_days' => $maxDays, 'daily_rate' => $dailyRate]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Vehicle $vehicle, array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $vehicle->id,
            'number_of_days' => 3,
            'meal_charged_to_client' => false,
            'fuel_charged_to_client' => false,
            'same_return_route' => true,
            'legs' => [
                'outbound' => [
                    ['from_point' => 'Antananarivo', 'to_point' => 'Toamasina', 'distance_km' => 450],
                ],
            ],
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('simulations.create'))->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_can_view_the_create_page(): void
    {
        $this->actingAs($this->user())
            ->get(route('simulations.create'))
            ->assertOk();
    }

    public function test_a_simulation_can_be_created_in_one_request(): void
    {
        $vehicle = $this->vehicle();
        $this->givenRentalRate($vehicle, 799, 1, 5, 250000);

        $response = $this->actingAs($this->user())
            ->post(route('simulations.store'), $this->payload($vehicle));

        $this->assertDatabaseCount('simulations', 1);
        // Le trajet aller est dupliqué en miroir pour le retour (same_return_route).
        $this->assertDatabaseCount('simulation_legs', 2);

        $simulation = Simulation::firstOrFail();
        $response->assertRedirect(route('simulations.show', $simulation));
    }

    public function test_it_returns_a_validation_error_when_no_tariff_matches(): void
    {
        $vehicle = $this->vehicle();

        $this->actingAs($this->user())
            ->post(route('simulations.store'), $this->payload($vehicle))
            ->assertSessionHasErrors('legs');

        $this->assertDatabaseCount('simulations', 0);
    }

    public function test_at_least_one_leg_is_required(): void
    {
        $vehicle = $this->vehicle();

        $this->actingAs($this->user())
            ->post(route('simulations.store'), $this->payload($vehicle, ['legs' => ['outbound' => []]]))
            ->assertSessionHasErrors('legs.outbound');
    }

    public function test_the_show_page_displays_the_simulation(): void
    {
        $vehicle = $this->vehicle();
        $this->givenRentalRate($vehicle, 799, 1, 5, 250000);
        $this->actingAs($this->user())->post(route('simulations.store'), $this->payload($vehicle));
        $simulation = Simulation::firstOrFail();

        $this->actingAs($this->user())
            ->get(route('simulations.show', $simulation))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('simulations/Show')
                ->has('simulation.legs', 2));
    }
}
