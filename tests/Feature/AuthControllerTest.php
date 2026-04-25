<?php
// Základní testy pro AuthController

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_login_form_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Přihlásit');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'username'  => 'testuser',
            'password'  => Hash::make('SuperHeslo123!'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'SuperHeslo123!',
        ]);

        $response->assertRedirect('/');
        $this->assertNotNull(session('user'));
        $this->assertEquals($user->id, session('user.id'));
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'username'  => 'testuser',
            'password'  => Hash::make('SuperHeslo123!'),
            'is_active' => true,
        ]);

        $this->post('/login', ['username' => 'testuser', 'password' => 'spatne'])
             ->assertSessionHasErrors('login');
        $this->assertNull(session('user'));
    }

    public function test_inactive_account_cannot_login(): void
    {
        User::factory()->create([
            'username'  => 'inactive',
            'password'  => Hash::make('SuperHeslo123!'),
            'is_active' => false,
        ]);

        $this->post('/login', ['username' => 'inactive', 'password' => 'SuperHeslo123!'])
             ->assertSessionHasErrors('login');
    }

    public function test_login_rate_limit_blocks_after_5_attempts(): void
    {
        RateLimiter::clear('testuser|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'testuser', 'password' => 'spatne']);
        }

        $this->post('/login', ['username' => 'testuser', 'password' => 'spatne'])
             ->assertSessionHasErrors('login')
             ->assertSessionHas('errors', fn($errors) =>
                 str_contains($errors->first('login'), 'Příliš mnoho pokusů')
             );
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        session(['user' => ['id' => $user->id, 'username' => $user->username]]);

        $this->get('/logout')->assertRedirect('/login');
        $this->assertNull(session('user'));
    }
}
