<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleControllerTest extends TestCase
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
            'name' => 'Starex 1',
            'brand' => 'Hyundai',
            'model' => 'Starex',
            'seats' => 8,
            'registration_number' => '1234 TBA',
            'year' => 2020,
            'has_air_conditioning' => true,
            'average_consumption' => 8.5,
            'status' => VehicleStatus::Available->value,
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('vehicles.index'))->assertRedirect(route('login'));
    }

    public function test_the_index_lists_vehicles(): void
    {
        Vehicle::factory()->count(3)->create();

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
        Vehicle::factory()->count(17)->create();

        $this->actingAs($this->user())
            ->get(route('vehicles.index'))
            ->assertInertia(fn ($page) => $page
                ->has('vehicles.data', 15)
                ->whereNot('vehicles.next_page_url', null));

        $this->actingAs($this->user())
            ->get(route('vehicles.index', ['page' => 2]))
            ->assertInertia(fn ($page) => $page->has('vehicles.data', 2));
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
        Vehicle::factory()->create(['registration_number' => '1234 TBA']);

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

        $vehicle = Vehicle::factory()->create([
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

        $vehicle = Vehicle::factory()->create([
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
        $vehicle = Vehicle::factory()->create(['registration_number' => '1234 TBA']);

        $this->actingAs($this->user())
            ->put(route('vehicles.update', $vehicle), $this->payload(['name' => 'Starex renommé']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Starex renommé', $vehicle->refresh()->name);
    }

    public function test_an_image_can_be_uploaded_through_the_spoofed_post_the_form_uses(): void
    {
        Storage::fake('public');

        $vehicle = Vehicle::factory()->create(['registration_number' => '1234 TBA']);

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
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->user())
            ->delete(route('vehicles.destroy', $vehicle))
            ->assertRedirect(route('vehicles.index'));

        $this->assertSoftDeleted($vehicle);
    }
}
