<?php

namespace Tests\Feature\Configuration;

use App\Models\Brand;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReferenceControllersTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::findOrCreate(Roles::SuperAdmin, 'web');

        return tap(User::factory()->create(['email_verified_at' => now()]))
            ->assignRole(Roles::SuperAdmin);
    }

    private function plainUser(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_guests_cannot_reach_the_configuration(): void
    {
        $this->get(route('configuration.brands.index'))->assertRedirect(route('login'));
    }

    public function test_a_plain_user_is_forbidden(): void
    {
        foreach ([
            'configuration.brands.index',
            'configuration.vehicle-types.index',
            'configuration.vehicle-models.index',
        ] as $routeName) {
            $this->actingAs($this->plainUser())->get(route($routeName))->assertForbidden();
        }
    }

    public function test_a_super_admin_reaches_every_configuration_page(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)->get(route('configuration.brands.index'))->assertOk();
        $this->actingAs($user)->get(route('configuration.vehicle-types.index'))->assertOk();
        $this->actingAs($user)->get(route('configuration.vehicle-models.index'))->assertOk();
    }

    public function test_the_super_admin_flag_is_shared_with_the_front_end(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('auth.isSuperAdmin', true));

        $this->actingAs($this->plainUser())
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('auth.isSuperAdmin', false));
    }

    public function test_a_brand_can_be_created_renamed_and_deleted(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->from(route('configuration.brands.index'))
            ->post(route('configuration.brands.store'), ['name' => 'Hyundai'])
            ->assertSessionHasNoErrors();

        $brand = Brand::firstOrFail();

        $this->actingAs($user)
            ->from(route('configuration.brands.index'))
            ->put(route('configuration.brands.update', $brand), ['name' => 'Hyundai Motor'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Hyundai Motor', $brand->fresh()->name);

        $this->actingAs($user)
            ->from(route('configuration.brands.index'))
            ->delete(route('configuration.brands.destroy', $brand));

        $this->assertDatabaseCount('brands', 0);
    }

    public function test_brand_names_stay_unique(): void
    {
        Brand::create(['name' => 'Toyota']);

        $this->actingAs($this->superAdmin())
            ->from(route('configuration.brands.index'))
            ->post(route('configuration.brands.store'), ['name' => 'Toyota'])
            ->assertSessionHasErrors('name');
    }

    public function test_a_brand_carrying_models_cannot_be_deleted(): void
    {
        $brand = Brand::create(['name' => 'Toyota']);
        VehicleModel::create(['brand_id' => $brand->id, 'name' => 'Hiace']);

        $this->actingAs($this->superAdmin())
            ->from(route('configuration.brands.index'))
            ->delete(route('configuration.brands.destroy', $brand))
            ->assertSessionHasErrors('brand');

        $this->assertDatabaseCount('brands', 1);
    }

    public function test_a_vehicle_type_can_be_managed(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->from(route('configuration.vehicle-types.index'))
            ->post(route('configuration.vehicle-types.store'), ['name' => 'Pick-up'])
            ->assertSessionHasNoErrors();

        $type = VehicleType::firstOrFail();

        $this->actingAs($user)
            ->from(route('configuration.vehicle-types.index'))
            ->put(route('configuration.vehicle-types.update', $type), ['name' => 'Pick-up double cabine'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Pick-up double cabine', $type->fresh()->name);
    }

    public function test_deleting_a_type_only_unclassifies_its_models(): void
    {
        $type = VehicleType::create(['name' => 'Bus', 'position' => 0]);
        $brand = Brand::create(['name' => 'Hyundai']);
        $model = VehicleModel::create([
            'brand_id' => $brand->id, 'vehicle_type_id' => $type->id, 'name' => 'County',
        ]);

        $this->actingAs($this->superAdmin())
            ->from(route('configuration.vehicle-types.index'))
            ->delete(route('configuration.vehicle-types.destroy', $type));

        $this->assertDatabaseCount('vehicle_types', 0);
        $this->assertNotNull($model->fresh());
        $this->assertNull($model->fresh()->vehicle_type_id);
    }

    public function test_a_model_is_created_under_a_brand_and_a_type(): void
    {
        $brand = Brand::create(['name' => 'Hyundai']);
        $type = VehicleType::create(['name' => 'Bus', 'position' => 0]);

        $this->actingAs($this->superAdmin())
            ->from(route('configuration.vehicle-models.index'))
            ->post(route('configuration.vehicle-models.store'), [
                'brand_id' => $brand->id,
                'vehicle_type_id' => $type->id,
                'name' => 'County',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vehicle_models', [
            'brand_id' => $brand->id, 'vehicle_type_id' => $type->id, 'name' => 'County',
        ]);
    }

    public function test_a_model_can_be_marked_supported(): void
    {
        $brand = Brand::create(['name' => 'Hyundai']);
        $type = VehicleType::create(['name' => 'Bus', 'position' => 0]);

        $this->actingAs($this->superAdmin())
            ->from(route('configuration.vehicle-models.index'))
            ->post(route('configuration.vehicle-models.store'), [
                'brand_id' => $brand->id,
                'vehicle_type_id' => $type->id,
                'name' => 'County',
                'is_supported' => true,
            ])
            ->assertSessionHasNoErrors();

        $model = VehicleModel::firstOrFail();
        $this->assertTrue((bool) $model->is_supported);

        $this->actingAs($this->superAdmin())
            ->from(route('configuration.vehicle-models.index'))
            ->put(route('configuration.vehicle-models.update', $model), [
                'brand_id' => $brand->id,
                'vehicle_type_id' => $type->id,
                'name' => 'County',
                'is_supported' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $model->fresh()->is_supported);
    }

    public function test_a_model_name_is_unique_per_brand_but_may_repeat_across_brands(): void
    {
        $hyundai = Brand::create(['name' => 'Hyundai']);
        $kia = Brand::create(['name' => 'Kia']);
        VehicleModel::create(['brand_id' => $hyundai->id, 'name' => 'Sorento']);

        $this->actingAs($this->superAdmin())
            ->from(route('configuration.vehicle-models.index'))
            ->post(route('configuration.vehicle-models.store'), [
                'brand_id' => $hyundai->id, 'name' => 'Sorento',
            ])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->superAdmin())
            ->from(route('configuration.vehicle-models.index'))
            ->post(route('configuration.vehicle-models.store'), [
                'brand_id' => $kia->id, 'name' => 'Sorento',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('vehicle_models', 2);
    }

    public function test_a_model_used_by_a_vehicle_cannot_be_deleted(): void
    {
        $model = VehicleModel::factory()->create();
        Vehicle::factory()->create(['vehicle_model_id' => $model->id]);

        $this->actingAs($this->superAdmin())
            ->from(route('configuration.vehicle-models.index'))
            ->delete(route('configuration.vehicle-models.destroy', $model))
            ->assertSessionHasErrors('model');

        $this->assertDatabaseCount('vehicle_models', 1);
    }

    public function test_models_can_be_filtered_by_type_and_by_absence_of_type(): void
    {
        $brand = Brand::create(['name' => 'Hyundai']);
        $type = VehicleType::create(['name' => 'Bus', 'position' => 0]);
        VehicleModel::create(['brand_id' => $brand->id, 'vehicle_type_id' => $type->id, 'name' => 'County']);
        VehicleModel::create(['brand_id' => $brand->id, 'name' => 'Getz']);

        $this->actingAs($this->superAdmin())
            ->get(route('configuration.vehicle-models.index', ['vehicle_type_id' => $type->id]))
            ->assertInertia(fn ($page) => $page->has('models.data', 1)
                ->where('models.data.0.name', 'County'));

        $this->actingAs($this->superAdmin())
            ->get(route('configuration.vehicle-models.index', ['vehicle_type_id' => 'none']))
            ->assertInertia(fn ($page) => $page->has('models.data', 1)
                ->where('models.data.0.name', 'Getz'));
    }

    public function test_models_can_be_searched_by_name(): void
    {
        $brand = Brand::create(['name' => 'Hyundai']);
        VehicleModel::create(['brand_id' => $brand->id, 'name' => 'Starex SVX']);
        VehicleModel::create(['brand_id' => $brand->id, 'name' => 'Getz']);

        $this->actingAs($this->superAdmin())
            ->get(route('configuration.vehicle-models.index', ['search' => 'starex']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('models.data', 1)
                ->where('models.data.0.name', 'Starex SVX'));
    }
}
