<?php


namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /** Pomocná metoda - přihlásit user-a do session */
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

    public function test_unauthenticated_redirects_to_login(): void
    {
        $this->get('/prehled')->assertRedirect('/login');
    }

    public function test_user_without_permission_gets_403(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->giveRoleTo('viewer');

        $this->actingAsSession($viewer)
             ->get('/admin')
             ->assertStatus(403);
    }

    public function test_admin_can_access_admin_section(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->giveRoleTo('admin');

        $this->actingAsSession($admin)
             ->get('/admin')
             ->assertOk();
    }

    public function test_super_admin_bypasses_permission_check(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->giveRoleTo('super_admin');

        // Super admin nemusí mít 'oopp.view' explicitně
        $this->actingAsSession($superAdmin)
             ->get('/prehled')
             ->assertOk();
    }
}
