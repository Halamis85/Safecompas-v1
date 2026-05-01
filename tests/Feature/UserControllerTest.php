<?php
// tests/Feature/UserControllerTest.php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class UserControllerTest extends TestCase
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

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        User::factory()->count(3)->create();

        $response = $this->actingAsSession($admin)->getJson('/adminUser');
        $response->assertOk();
    }

    public function test_admin_can_create_user_with_strong_password(): void
    {
        $admin = User::factory()->withRole('admin')->create();

        $response = $this->actingAsSession($admin)->postJson('/add_users', [
            'firstname' => 'Jan',
            'lastname'  => 'Novák',
            'email'     => 'jan.novak@gmail.com',
            'username'  => 'jnovak123',
            'password'  => 'SuperSilneHeslo123!',
            'role'      => 'admin',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'username' => 'jnovak123',
            'email'    => 'jan.novak@gmail.com',
        ]);
    }

    public function test_create_user_rejects_weak_password(): void
    {
        $admin = User::factory()->withRole('admin')->create();

        $this->actingAsSession($admin)->postJson('/add_users', [
            'firstname' => 'Jan',
            'lastname'  => 'Novák',
            'email'     => 'jan.novak@gmail.com',
            'username'  => 'jnovak',
            'password'  => 'kratke',  // pod 12 znaků
            'role'      => 'admin',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['password']);
    }

    public function test_only_super_admin_can_create_super_admin(): void
    {
        $admin = User::factory()->withRole('admin')->create();

        $this->actingAsSession($admin)->postJson('/add_users', [
            'firstname' => 'Spy',
            'lastname'  => 'Master',
            'email'     => 'spy@gmail.com',
            'username'  => 'spymaster',
            'password'  => 'SuperSilneHeslo123!',
            'role'      => 'super_admin',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('users', ['username' => 'spymaster']);
    }

    public function test_send_login_email_returns_generic_response_for_nonexistent_user(): void
    {
        $admin = User::factory()->withRole('admin')->create();

        $response = $this->actingAsSession($admin)->postJson('/send-login-email', [
            'username'  => 'doesnotexist',
            'email'     => 'nope@example.com',
            'firstname' => 'Doesnot',
            'lastname'  => 'Exist',
        ]);

        // Generická odpověď - chrání před username enumeration
        $response->assertOk()->assertJson(['status' => 'success']);
    }

    public function test_send_login_email_for_real_user_resets_password(): void
    {
        Notification::fake();
        $admin = User::factory()->withRole('admin')->create();
        $target = User::factory()->create([
            'firstname' => 'Cíl',
            'lastname'  => 'Reset',
            'email'     => 'reset@gmail.com',
        ]);
        $oldHash = $target->password;

        $this->actingAsSession($admin)->postJson('/send-login-email', [
            'username'  => $target->username,
            'email'     => $target->email,
            'firstname' => $target->firstname,
            'lastname'  => $target->lastname,
        ])->assertOk();

        // Heslo se v DB změnilo
        $this->assertNotEquals($oldHash, $target->fresh()->password);

        // Email byl poslán
        Notification::assertSentTo($target, \App\Notifications\LoginReset::class);
    }

    public function test_send_login_email_is_rate_limited(): void
    {
        $admin = User::factory()->withRole('admin')->create();

        // Rate limit je 10/min na IP
        RateLimiter::clear('reset.127.0.0.1');

        for ($i = 0; $i < 10; $i++) {
            $this->actingAsSession($admin)->postJson('/send-login-email', [
                'username'  => 'foo',
                'email'     => 'foo@gmail.com',
                'firstname' => 'F',
                'lastname'  => 'oo',
            ]);
        }

        // 11. pokus už musí být 429
        $this->actingAsSession($admin)->postJson('/send-login-email', [
            'username'  => 'foo',
            'email'     => 'foo@gmail.com',
            'firstname' => 'F',
            'lastname'  => 'oo',
        ])->assertStatus(429);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->withRole('admin')->create();

        $this->actingAsSession($admin)->deleteJson("/users/{$admin->id}");

        // Admin nesmí smazat sám sebe - bez 3. argumentu (ten je connection name, ne message)
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
