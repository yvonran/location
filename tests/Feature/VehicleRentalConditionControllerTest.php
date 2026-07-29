<?php

namespace Tests\Feature;

use App\Enums\RentalZone;
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
            'city_max_km' => 50,
            'suburb_max_km' => 100,
            'long_distance_max_km' => 700,
            'rates' => [
                ['zone' => 'city', 'min_days' => 1, 'max_days' => 5, 'daily_rate' => 180000],
                ['zone' => 'city', 'min_days' => 6, 'max_days' => null, 'daily_rate' => 160000],
            ],
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->get(route('vehicles.conditions.edit', $vehicle))->assertRedirect(route('login'));
    }

    public function test_the_form_opens_with_the_default_thresholds_for_a_vehicle_without_conditions(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->get(route('vehicles.conditions.edit', $vehicle))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vehicles/RentalCondition')
                ->where('condition.city_max_km', 50)
                ->where('condition.suburb_max_km', 100)
                ->where('condition.long_distance_max_km', 700)
                ->has('rates', 0)
                ->has('zones', 4));
    }

    public function test_the_vehicle_edit_page_carries_the_same_conditions_props(): void
    {
        $vehicle = Vehicle::factory()->create();
        $condition = RentalCondition::create([
            'vehicle_id' => $vehicle->id,
            'city_max_km' => 35,
        ]);
        $condition->rentalRates()->create([
            'zone' => RentalZone::City, 'min_days' => 1, 'max_days' => null, 'daily_rate' => 180000,
        ]);

        $this->actingAs($this->user())
            ->get(route('vehicles.edit', $vehicle))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vehicles/Edit')
                ->where('condition.city_max_km', 35)
                ->has('rates', 1)
                ->has('zones', 4));
    }

    public function test_saving_conditions_from_the_vehicle_edit_page_returns_there(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->from(route('vehicles.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload())
            ->assertRedirect(route('vehicles.edit', $vehicle));

        $this->assertDatabaseCount('rental_rates', 2);
    }

    public function test_conditions_and_rates_are_created_on_first_save(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload())
            ->assertRedirect(route('vehicles.conditions.edit', $vehicle));

        $condition = $vehicle->fresh()->rentalCondition;

        $this->assertNotNull($condition);
        $this->assertSame(50, $condition->city_max_km);
        $this->assertCount(2, $condition->rentalRates);
        $this->assertDatabaseHas('rental_rates', [
            'rental_condition_id' => $condition->id,
            'zone' => 'city',
            'min_days' => 6,
            'max_days' => null,
            'daily_rate' => 160000,
        ]);
    }

    public function test_saving_replaces_the_previous_grid_instead_of_appending(): void
    {
        $vehicle = Vehicle::factory()->create();
        $condition = RentalCondition::create(['vehicle_id' => $vehicle->id]);
        $condition->rentalRates()->create([
            'zone' => RentalZone::VeryLongDistance,
            'min_days' => 1, 'max_days' => null, 'daily_rate' => 999000,
        ]);

        $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame(1, RentalCondition::where('vehicle_id', $vehicle->id)->count());
        $this->assertCount(2, $condition->fresh()->rentalRates);
        $this->assertDatabaseMissing('rental_rates', ['daily_rate' => 999000]);
    }

    public function test_the_thresholds_must_increase(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload([
                'city_max_km' => 100,
                'suburb_max_km' => 60,
            ]))
            ->assertSessionHasErrors('suburb_max_km');

        $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload([
                'long_distance_max_km' => 90,
            ]))
            ->assertSessionHasErrors('long_distance_max_km');
    }

    public function test_overlapping_day_ranges_in_the_same_zone_are_rejected(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload([
                'rates' => [
                    ['zone' => 'city', 'min_days' => 1, 'max_days' => 5, 'daily_rate' => 180000],
                    ['zone' => 'city', 'min_days' => 4, 'max_days' => 9, 'daily_rate' => 160000],
                ],
            ]))
            ->assertSessionHasErrors('rates.1.min_days');

        $this->assertDatabaseCount('rental_rates', 0);
    }

    public function test_an_open_ended_range_overlapping_a_later_one_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload([
                'rates' => [
                    ['zone' => 'city', 'min_days' => 1, 'max_days' => null, 'daily_rate' => 180000],
                    ['zone' => 'city', 'min_days' => 6, 'max_days' => 10, 'daily_rate' => 160000],
                ],
            ]))
            ->assertSessionHasErrors('rates.1.min_days');
    }

    public function test_the_same_day_range_in_two_different_zones_is_allowed(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload([
                'rates' => [
                    ['zone' => 'city', 'min_days' => 1, 'max_days' => 5, 'daily_rate' => 180000],
                    ['zone' => 'suburb', 'min_days' => 1, 'max_days' => 5, 'daily_rate' => 220000],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('rental_rates', 2);
    }

    public function test_a_rate_must_have_a_valid_zone_and_a_positive_amount(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload([
                'rates' => [['zone' => 'campagne', 'min_days' => 1, 'max_days' => null, 'daily_rate' => 1]],
            ]))
            ->assertSessionHasErrors('rates.0.zone');

        $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload([
                'rates' => [['zone' => 'city', 'min_days' => 1, 'max_days' => null, 'daily_rate' => -5]],
            ]))
            ->assertSessionHasErrors('rates.0.daily_rate');
    }

    public function test_the_max_days_cannot_be_lower_than_the_min_days(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload([
                'rates' => [['zone' => 'city', 'min_days' => 8, 'max_days' => 3, 'daily_rate' => 180000]],
            ]))
            ->assertSessionHasErrors('rates.0.max_days');
    }

    public function test_the_grid_can_be_emptied(): void
    {
        $vehicle = Vehicle::factory()->create();
        $condition = RentalCondition::create(['vehicle_id' => $vehicle->id]);
        $condition->rentalRates()->create([
            'zone' => RentalZone::City, 'min_days' => 1, 'max_days' => null, 'daily_rate' => 180000,
        ]);

        $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload(['rates' => []]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('rental_rates', 0);
    }

    public function test_the_saved_grid_is_returned_when_reopening_the_form(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->from(route('vehicles.conditions.edit', $vehicle))
            ->put(route('vehicles.conditions.update', $vehicle), $this->payload(['city_max_km' => 35]));

        $this->actingAs($this->user())
            ->get(route('vehicles.conditions.edit', $vehicle))
            ->assertInertia(fn ($page) => $page
                ->where('condition.city_max_km', 35)
                ->has('rates', 2));
    }
}
