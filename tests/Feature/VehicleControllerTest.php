<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\Brand;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleControllerTest extends TestCase
{
    use RefreshDatabase;

    private ?User $actingUser = null;

    private function user(): User
    {
        // Mémorisé : le cloisonnement impose que l'acteur et les données
        // manipulées soient bien le même compte d'un bout à l'autre du test.
        return $this->actingUser ??= User::factory()->create(['email_verified_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Starex 1',
            'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8,
            'registration_number' => '1234 TBA',
            'year' => 2020,
            'has_air_conditioning' => true,
            'average_consumption' => 8.5,
            'status' => VehicleStatus::Available->value,
            // Exigées à la création : le véhicule et ses zones partent ensemble.
            'zones' => [
                [
                    'name' => 'Ville',
                    'max_km' => 50,
                    'rates' => [['min_days' => 1, 'max_days' => null, 'daily_rate' => 180000]],
                ],
                [
                    'name' => 'Reste',
                    'max_km' => null,
                    'rates' => [['min_days' => 1, 'max_days' => null, 'daily_rate' => 350000]],
                ],
            ],
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('vehicles.index'))->assertRedirect(route('login'));
    }

    public function test_the_index_lists_vehicles(): void
    {
        Vehicle::factory()->for($this->user())->count(3)->create();

        $this->actingAs($this->user())
            ->get(route('vehicles.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vehicles/Index')
                ->has('vehicles.data', 3)
                ->where('vehicles.total', 3));
    }

    public function test_the_index_paginates_beyond_the_first_page(): void
    {
        Vehicle::factory()->for($this->user())->count(17)->create();

        $this->actingAs($this->user())
            ->get(route('vehicles.index'))
            ->assertInertia(fn ($page) => $page
                ->has('vehicles.data', 15)
                ->whereNot('vehicles.next_page_url', null));

        $this->actingAs($this->user())
            ->get(route('vehicles.index', ['page' => 2]))
            ->assertInertia(fn ($page) => $page->has('vehicles.data', 2));
    }

    public function test_the_create_page_only_lists_supported_typed_models(): void
    {
        $type = VehicleType::create(['name' => 'Bus', 'position' => 0]);
        $brand = Brand::create(['name' => 'Hyundai']);

        $supported = VehicleModel::create([
            'brand_id' => $brand->id, 'vehicle_type_id' => $type->id, 'name' => 'County', 'is_supported' => true,
        ]);
        VehicleModel::create([
            'brand_id' => $brand->id, 'vehicle_type_id' => $type->id, 'name' => 'Starex', 'is_supported' => false,
        ]);
        VehicleModel::create([
            'brand_id' => $brand->id, 'vehicle_type_id' => null, 'name' => 'Importé', 'is_supported' => true,
        ]);

        $this->actingAs($this->user())
            ->get(route('vehicles.create'))
            ->assertInertia(fn ($page) => $page
                ->has('vehicleModels', 1)
                ->where('vehicleModels.0.id', $supported->id));
    }

    public function test_the_edit_page_still_includes_the_vehicles_own_excluded_model(): void
    {
        $model = VehicleModel::factory()->create(['vehicle_type_id' => null, 'is_supported' => false]);
        $vehicle = Vehicle::factory()->for($this->user())->create(['vehicle_model_id' => $model->id]);

        $this->actingAs($this->user())
            ->get(route('vehicles.edit', $vehicle))
            ->assertInertia(fn ($page) => $page
                ->has('vehicleModels', 1)
                ->where('vehicleModels.0.id', $model->id));
    }

    public function test_a_vehicle_can_be_created_without_an_image(): void
    {
        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload())
            ->assertRedirect(route('vehicles.index'));

        $this->assertDatabaseHas('vehicles', [
            'registration_number' => '1234 TBA',
            'image_path' => null,
        ]);
    }

    public function test_the_create_page_offers_the_default_zones(): void
    {
        $this->actingAs($this->user())
            ->get(route('vehicles.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vehicles/Create')
                ->has('zones', 4)
                ->where('zones.0.name', 'Ville')
                ->where('zones.0.max_km', 50)
                ->where('zones.3.max_km', null));
    }

    public function test_the_rental_conditions_are_saved_along_with_the_new_vehicle(): void
    {
        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload())
            ->assertRedirect(route('vehicles.index'));

        $condition = Vehicle::firstOrFail()->rentalCondition;
        $zones = $condition->rentalZones()->orderBy('position')->get();

        $this->assertCount(2, $zones);
        $this->assertSame(['Ville', 'Reste'], $zones->pluck('name')->all());
        $this->assertSame(50, $zones[0]->max_km);
        $this->assertNull($zones[1]->max_km);
        $this->assertSame('180000.00', $zones[0]->rentalRates->first()->daily_rate);
    }

    public function test_a_vehicle_can_be_created_with_custom_zones(): void
    {
        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload([
                'zones' => [
                    ['name' => 'Intra-muros', 'max_km' => 15, 'rates' => []],
                    ['name' => 'Grand Tana', 'max_km' => 60, 'rates' => []],
                    ['name' => 'Ailleurs', 'max_km' => null, 'rates' => [['min_days' => 1, 'max_days' => null, 'daily_rate' => 400000]]],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ['Intra-muros', 'Grand Tana', 'Ailleurs'],
            Vehicle::firstOrFail()->rentalCondition->rentalZones()->orderBy('position')->pluck('name')->all(),
        );
    }

    public function test_invalid_zones_block_the_vehicle_creation_entirely(): void
    {
        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload([
                'zones' => [
                    ['name' => 'A', 'max_km' => 100, 'rates' => []],
                    ['name' => 'B', 'max_km' => 60, 'rates' => []],
                    ['name' => 'C', 'max_km' => null, 'rates' => []],
                ],
            ]))
            ->assertSessionHasErrors('zones.1.max_km');

        $this->assertDatabaseCount('vehicles', 0);
        $this->assertDatabaseCount('rental_conditions', 0);
    }

    public function test_at_least_one_zone_is_required_to_create_a_vehicle(): void
    {
        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload(['zones' => []]))
            ->assertSessionHasErrors('zones');

        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_zones_survive_the_multipart_upload_the_form_actually_uses(): void
    {
        Storage::fake('public');

        // En multipart, tout arrive en chaîne et les null deviennent des vides.
        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload([
                'image' => UploadedFile::fake()->image('starex.jpg'),
                'zones' => [
                    ['name' => 'Ville', 'max_km' => '50', 'rates' => [['min_days' => '1', 'max_days' => '5', 'daily_rate' => '180000']]],
                    ['name' => 'Reste', 'max_km' => '', 'rates' => [['min_days' => '1', 'max_days' => '', 'daily_rate' => '350000']]],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $zones = Vehicle::firstOrFail()->rentalCondition->rentalZones()->orderBy('position')->get();

        $this->assertSame(50, $zones[0]->max_km);
        $this->assertNull($zones[1]->max_km);
        $this->assertNull($zones[1]->rentalRates->first()->max_days);
        $this->assertSame('350000.00', $zones[1]->rentalRates->first()->daily_rate);
    }

    public function test_updating_a_vehicle_leaves_its_zones_untouched(): void
    {
        $this->actingAs($this->user())->post(route('vehicles.store'), $this->payload());

        $vehicle = Vehicle::firstOrFail();

        $this->actingAs($this->user())
            ->put(route('vehicles.update', $vehicle), $this->payload([
                'name' => 'Renommé',
                'zones' => [],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Renommé', $vehicle->fresh()->name);
        $this->assertCount(2, $vehicle->fresh()->rentalCondition->rentalZones);
    }

    public function test_a_vehicle_can_be_created_with_an_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload([
                'image' => UploadedFile::fake()->image('starex.jpg'),
            ]))
            ->assertRedirect(route('vehicles.index'));

        $vehicle = Vehicle::firstOrFail();

        $this->assertNotNull($vehicle->image_path);
        Storage::disk('public')->assertExists($vehicle->image_path);
    }

    public function test_the_average_consumption_is_stored_and_is_optional(): void
    {
        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload(['average_consumption' => 12.35]))
            ->assertSessionHasNoErrors();

        $this->assertSame('12.35', Vehicle::firstOrFail()->average_consumption);

        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload([
                'registration_number' => '5678 TBT',
                'average_consumption' => null,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNull(
            Vehicle::where('registration_number', '5678 TBT')->firstOrFail()->average_consumption,
        );
    }

    public function test_the_average_consumption_must_be_a_positive_number(): void
    {
        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload(['average_consumption' => -1]))
            ->assertSessionHasErrors('average_consumption');

        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload(['average_consumption' => 'beaucoup']))
            ->assertSessionHasErrors('average_consumption');
    }

    public function test_the_registration_number_must_be_unique(): void
    {
        Vehicle::factory()->for($this->user())->create(['registration_number' => '1234 TBA']);

        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload())
            ->assertSessionHasErrors('registration_number');
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->user())
            ->post(route('vehicles.store'), $this->payload([
                'image' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('image');
    }

    public function test_updating_a_vehicle_replaces_the_previous_image(): void
    {
        Storage::fake('public');

        $vehicle = Vehicle::factory()->for($this->user())->create([
            'image_path' => UploadedFile::fake()->image('old.jpg')->store('vehicles', 'public'),
        ]);
        $oldPath = $vehicle->image_path;

        $this->actingAs($this->user())
            ->put(route('vehicles.update', $vehicle), $this->payload([
                'registration_number' => $vehicle->registration_number,
                'image' => UploadedFile::fake()->image('new.jpg'),
            ]))
            ->assertRedirect(route('vehicles.index'));

        $vehicle->refresh();

        $this->assertNotSame($oldPath, $vehicle->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($vehicle->image_path);
    }

    public function test_the_image_can_be_removed_on_update(): void
    {
        Storage::fake('public');

        $vehicle = Vehicle::factory()->for($this->user())->create([
            'image_path' => UploadedFile::fake()->image('old.jpg')->store('vehicles', 'public'),
        ]);
        $oldPath = $vehicle->image_path;

        $this->actingAs($this->user())
            ->put(route('vehicles.update', $vehicle), $this->payload([
                'registration_number' => $vehicle->registration_number,
                'remove_image' => true,
            ]))
            ->assertRedirect(route('vehicles.index'));

        $this->assertNull($vehicle->refresh()->image_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_a_vehicle_keeps_its_own_registration_number_on_update(): void
    {
        $vehicle = Vehicle::factory()->for($this->user())->create(['registration_number' => '1234 TBA']);

        $this->actingAs($this->user())
            ->put(route('vehicles.update', $vehicle), $this->payload(['name' => 'Starex renommé']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Starex renommé', $vehicle->refresh()->name);
    }

    public function test_an_image_can_be_uploaded_through_the_spoofed_post_the_form_uses(): void
    {
        Storage::fake('public');

        $vehicle = Vehicle::factory()->for($this->user())->create(['registration_number' => '1234 TBA']);

        $this->actingAs($this->user())
            ->post(route('vehicles.update', $vehicle), $this->payload([
                '_method' => 'put',
                'image' => UploadedFile::fake()->image('starex.jpg'),
            ]))
            ->assertRedirect(route('vehicles.index'));

        $vehicle->refresh();

        $this->assertNotNull($vehicle->image_path);
        Storage::disk('public')->assertExists($vehicle->image_path);
    }

    public function test_a_vehicle_can_be_deleted(): void
    {
        $vehicle = Vehicle::factory()->for($this->user())->create();

        $this->actingAs($this->user())
            ->delete(route('vehicles.destroy', $vehicle))
            ->assertRedirect(route('vehicles.index'));

        $this->assertSoftDeleted($vehicle);
    }
}
