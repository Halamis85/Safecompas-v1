<?php
// tests/Feature/RolePermissionControllerTest.php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function actingAsSession(User $user): self
    {
        session([
            'user' => [
                'id'              => $user->id,
                'username'        => $user->username,
                'roles'           => $user->roles->pluck('name')->toArray(),
                'permissions'     => $user->roles->flatMap->permissions->pluck('name')->unique()->values()->toArray(),
                'is_super_admin'  => $user->hasRole('super_admin'),
                'is_admin'        => $user->hasRole('admin') || $user->hasRole('super_admin'),
            ],
            'last_activity' => time(),
        ]);
        return $this;
    }

    public function test_assign_roles_to_user_replaces_old_roles(): void
    {
        $admin  = User::factory()->withRole('super_admin')->create();
        $target = User::factory()->withRole('viewer')->create();

        $editorRole = Role::where('name', 'editor')->first()
            ?? Role::where('name', 'admin')->first(); // fallback na admin pokud editor v seederu není

        $this->assertNotNull($editorRole, 'Test vyžaduje, aby seeder měl alespoň role admin');

        $response = $this->actingAsSession($admin)
            ->postJson("/api/permissions/users/{$target->id}/roles", [
                'role_ids' => [$editorRole->id],
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $target->refresh()->load('roles');
        $this->assertEquals([$editorRole->id], $target->roles->pluck('id')->toArray());
    }

    public function test_assign_roles_with_empty_array_removes_all_roles(): void
    {
        $admin  = User::factory()->withRole('super_admin')->create();
        $target = User::factory()->withRole('admin')->create();

        $this->actingAsSession($admin)
            ->postJson("/api/permissions/users/{$target->id}/roles", [
                'role_ids' => [],
            ])
            ->assertOk();

        $target->refresh()->load('roles');
        $this->assertCount(0, $target->roles);
    }

    public function test_user_without_admin_permissions_cannot_assign_roles(): void
    {
        $viewer = User::factory()->withRole('viewer')->create();
        $target = User::factory()->create();

        $this->actingAsSession($viewer)
            ->postJson("/api/permissions/users/{$target->id}/roles", [
                'role_ids' => [1],
            ])
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_assign_roles(): void
    {
        $target = User::factory()->create();

        // Nezamícháváme do session - simulujeme neauth requesti
        $this->postJson("/api/permissions/users/{$target->id}/roles", [
            'role_ids' => [1],
        ])->assertStatus(401);
    }

    public function test_get_user_permissions_returns_roles_and_permissions(): void
    {
        $admin  = User::factory()->withRole('super_admin')->create();
        $target = User::factory()->withRole('admin')->create();

        $response = $this->actingAsSession($admin)
            ->getJson("/api/permissions/users/{$target->id}/permissions");

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'username', 'roles'],
                'permissions',
                'lekarnicky_access',
            ]);

        $data = $response->json();
        $this->assertEquals($target->id, $data['user']['id']);
        $this->assertNotEmpty($data['user']['roles']);
    }

    public function test_dashboard_returns_all_data_for_super_admin(): void
    {
        $admin = User::factory()->withRole('super_admin')->create();

        $response = $this->actingAsSession($admin)
            ->getJson('/api/permissions/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'roles',
                'permissions',
                'users',
                'lekarnicke',
            ]);
    }
}
