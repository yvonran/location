<?php

namespace Tests\Feature;

use App\Models\RentalCondition;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleRentalConditionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'zones' => [
                [
                    'name' => 'Ville',
                    'max_km' => 50,
                    'rates' => [
                        ['min_days' => 1, 'max_days' => 5, 'daily_rate' => 180000],
                        ['min_days' => 6, 'max_days' => null, 'daily_rate' => 160000],
                    ],
                ],
                [
                    'name' => 'Reste',
                    'max_km' => null,
                    'rates' => [
                        ['min_days' => 1, 'max_days' => null, 'daily_rate' => 350000],
                    ],
                ],
            ],
        ], $overrides);
    }

    private function save(Vehicle $vehicle, array $payload)
    {
        return $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $payload);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->get(route('vehicles.conditions.edit', $vehicle))->assertRedirect(route('login'));
    }

    public function test_the_form_opens_with_the_default_zones_for_a_vehicle_without_conditions(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->get(route('vehicles.conditions.edit', $vehicle))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vehicles/RentalCondition')
                ->has('zones', 4)
                ->where('zones.0.name', 'Ville')
                ->where('zones.0.max_km', 50)
                ->where('zones.3.max_km', null));
    }

    public function test_the_vehicle_edit_page_carries_the_same_zones(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->save($vehicle, $this->payload());

        $this->actingAs($this->user())
            ->get(route('vehicles.edit', $vehicle))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vehicles/Edit')
                ->has('zones', 2)
                ->where('zones.0.name', 'Ville'));
    }

    public function test_saving_conditions_from_the_vehicle_edit_page_returns_there(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->from(route('vehicles.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload())
            ->assertRedirect(route('vehicles.edit', $vehicle));
    }

    public function test_zones_and_rates_are_created_on_first_save(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->save($vehicle, $this->payload())
            ->assertRedirect(route('vehicles.conditions.edit', $vehicle));

        $condition = $vehicle->fresh()->rentalCondition;
        $zones = $condition->rentalZones()->orderBy('position')->get();

        $this->assertCount(2, $zones);
        $this->assertSame('Ville', $zones[0]->name);
        $this->assertSame(50, $zones[0]->max_km);
        $this->assertSame(0, $zones[0]->position);
        $this->assertNull($zones[1]->max_km);
        $this->assertSame(1, $zones[1]->position);
        $this->assertCount(2, $zones[0]->rentalRates);
        $this->assertCount(1, $zones[1]->rentalRates);
    }

    public function test_a_user_can_define_any_number_of_zones_with_their_own_names(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->save($vehicle, [
            'zones' => [
                ['name' => 'Intra-muros', 'max_km' => 15, 'rates' => [['min_days' => 1, 'max_days' => null, 'daily_rate' => 120000]]],
                ['name' => 'Grand Tana', 'max_km' => 60, 'rates' => []],
                ['name' => 'Région', 'max_km' => 250, 'rates' => []],
                ['name' => 'Province', 'max_km' => 900, 'rates' => []],
                ['name' => 'Grand Sud', 'max_km' => null, 'rates' => [['min_days' => 1, 'max_days' => null, 'daily_rate' => 500000]]],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            ['Intra-muros', 'Grand Tana', 'Région', 'Province', 'Grand Sud'],
            $vehicle->fresh()->rentalCondition->rentalZones()->orderBy('position')->pluck('name')->all(),
        );
    }

    public function test_a_single_open_zone_is_enough(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->save($vehicle, [
            'zones' => [
                ['name' => 'Tarif unique', 'max_km' => null, 'rates' => [['min_days' => 1, 'max_days' => null, 'daily_rate' => 200000]]],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('rental_zones', 1);
        $this->assertDatabaseCount('rental_rates', 1);
    }

    public function test_saving_replaces_the_previous_zones_instead_of_appending(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->save($vehicle, $this->payload())->assertSessionHasNoErrors();

        $this->save($vehicle, [
            'zones' => [
                ['name' => 'Unique', 'max_km' => null, 'rates' => [['min_days' => 1, 'max_days' => null, 'daily_rate' => 999000]]],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, RentalCondition::where('vehicle_id', $vehicle->id)->count());
        $this->assertDatabaseCount('rental_zones', 1);
        $this->assertDatabaseCount('rental_rates', 1);
        $this->assertDatabaseHas('rental_zones', ['name' => 'Unique']);
    }

    public function test_at_least_one_zone_is_required(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->save($vehicle, ['zones' => []])->assertSessionHasErrors('zones');
    }

    public function test_a_zone_needs_a_name(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->save($vehicle, [
            'zones' => [['name' => '', 'max_km' => null, 'rates' => []]],
        ])->assertSessionHasErrors('zones.0.name');
    }

    public function test_the_bounds_must_increase(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->save($vehicle, [
            'zones' => [
                ['name' => 'A', 'max_km' => 100, 'rates' => []],
                ['name' => 'B', 'max_km' => 60, 'rates' => []],
                ['name' => 'C', 'max_km' => null, 'rates' => []],
            ],
        ])->assertSessionHasErrors('zones.1.max_km');
    }

    public function test_only_the_last_zone_may_be_open_ended(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->save($vehicle, [
            'zones' => [
                ['name' => 'A', 'max_km' => null, 'rates' => []],
                ['name' => 'B', 'max_km' => 100, 'rates' => []],
            ],
        ])->assertSessionHasErrors('zones.0.max_km');
    }

    public function test_the_last_zone_must_be_open_ended(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->save($vehicle, [
            'zones' => [
                ['name' => 'A', 'max_km' => 50, 'rates' => []],
                ['name' => 'B', 'max_km' => 100, 'rates' => []],
            ],
        ])->assertSessionHasErrors('zones.1.max_km');
    }

    public function test_overlapping_day_ranges_in_the_same_zone_are_rejected(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->save($vehicle, [
            'zones' => [[
                'name' => 'Ville',
                'max_km' => null,
                'rates' => [
                    ['min_days' => 1, 'max_days' => 5, 'daily_rate' => 180000],
                    ['min_days' => 4, 'max_days' => 9, 'daily_rate' => 160000],
                ],
            ]],
        ])->assertSessionHasErrors('zones.0.rates.1.min_days');

        $this->assertDatabaseCount('rental_rates', 0);
    }

    public function test_the_same_day_range_in_two_different_zones_is_allowed(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->save($vehicle, [
            'zones' => [
                ['name' => 'A', 'max_km' => 50, 'rates' => [['min_days' => 1, 'max_days' => 5, 'daily_rate' => 180000]]],
                ['name' => 'B', 'max_km' => null, 'rates' => [['min_days' => 1, 'max_days' => 5, 'daily_rate' => 220000]]],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('rental_rates', 2);
    }

    public function test_a_rate_must_carry_a_positive_amount_and_a_coherent_range(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->save($vehicle, [
            'zones' => [['name' => 'A', 'max_km' => null, 'rates' => [['min_days' => 1, 'max_days' => null, 'daily_rate' => -5]]]],
        ])->assertSessionHasErrors('zones.0.rates.0.daily_rate');

        $this->save($vehicle, [
            'zones' => [['name' => 'A', 'max_km' => null, 'rates' => [['min_days' => 8, 'max_days' => 3, 'daily_rate' => 180000]]]],
        ])->assertSessionHasErrors('zones.0.rates.0.max_days');
    }

    public function test_a_zone_can_be_saved_without_any_rate(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->save($vehicle, [
            'zones' => [['name' => 'À tarifer plus tard', 'max_km' => null, 'rates' => []]],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('rental_zones', 1);
        $this->assertDatabaseCount('rental_rates', 0);
    }

    public function test_the_saved_zones_are_returned_when_reopening_the_form(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->save($vehicle, $this->payload());

        $this->actingAs($this->user())
            ->get(route('vehicles.conditions.edit', $vehicle))
            ->assertInertia(fn ($page) => $page
                ->has('zones', 2)
                ->where('zones.0.name', 'Ville')
                ->where('zones.0.max_km', 50)
                ->has('zones.0.rates', 2)
                ->where('zones.1.max_km', null));
    }
}
