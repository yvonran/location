<?php

namespace Tests\Feature\Configuration;

use App\Models\SimulationSetting;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SimulationSettingControllerTest extends TestCase
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

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('configuration.simulation-settings.edit'))->assertRedirect(route('login'));
    }

    public function test_a_plain_user_is_forbidden(): void
    {
        $this->actingAs($this->plainUser())
            ->get(route('configuration.simulation-settings.edit'))
            ->assertForbidden();
    }

    public function test_a_super_admin_sees_the_current_settings(): void
    {
        SimulationSetting::current()->update(['fuel_price_per_liter' => 6000, 'client_meal_price' => 8000]);

        $this->actingAs($this->superAdmin())
            ->get(route('configuration.simulation-settings.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('setting.fuel_price_per_liter', '6000.00')
                ->where('setting.client_meal_price', '8000.00'));
    }

    public function test_a_super_admin_can_update_the_settings(): void
    {
        $this->actingAs($this->superAdmin())
            ->from(route('configuration.simulation-settings.edit'))
            ->put(route('configuration.simulation-settings.update'), [
                'fuel_price_per_liter' => 5500,
                'client_meal_price' => 7500,
            ])
            ->assertSessionHasNoErrors();

        $setting = SimulationSetting::current();

        $this->assertSame('5500.00', (string) $setting->fuel_price_per_liter);
        $this->assertSame('7500.00', (string) $setting->client_meal_price);
    }

    public function test_the_amounts_must_be_non_negative_numbers(): void
    {
        $this->actingAs($this->superAdmin())
            ->from(route('configuration.simulation-settings.edit'))
            ->put(route('configuration.simulation-settings.update'), [
                'fuel_price_per_liter' => -1,
                'client_meal_price' => 'beaucoup',
            ])
            ->assertSessionHasErrors(['fuel_price_per_liter', 'client_meal_price']);
    }
}
