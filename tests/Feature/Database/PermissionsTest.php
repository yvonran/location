<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_role_can_be_created_and_assigned_to_a_user(): void
    {
        Role::create(['name' => 'agent', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('agent');

        $this->assertTrue($user->fresh()->hasRole('agent'));
    }
}
