<?php
// tests/Feature/UserFactoryTest.php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_factory_creates_complete_user(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->id);
        $this->assertNotEmpty($user->firstname);
        $this->assertNotEmpty($user->lastname);
        $this->assertNotEmpty($user->username);
        $this->assertNotEmpty($user->email);
        $this->assertNotEmpty($user->password);
        $this->assertTrue($user->is_active);
    }

    public function test_factory_creates_user_with_unique_username(): void
    {
        // Create 50 users in row - bez unique implementace by někde padlo
        $users = User::factory()->count(50)->create();

        $usernames = $users->pluck('username')->toArray();
        $this->assertCount(50, array_unique($usernames),
            'Faktory generuje duplikátní username');
    }

    public function test_factory_creates_user_with_unique_email(): void
    {
        $users = User::factory()->count(20)->create();

        $emails = $users->pluck('email')->toArray();
        $this->assertCount(20, array_unique($emails),
            'Faktory generuje duplikátní emaily');
    }

    public function test_inactive_state_creates_inactive_user(): void
    {
        $user = User::factory()->inactive()->create();
        $this->assertFalse($user->is_active);
    }

    public function test_with_password_state_uses_provided_password(): void
    {
        $user = User::factory()->withPassword('TajneHeslo123!')->create();

        $this->assertTrue(Hash::check('TajneHeslo123!', $user->password));
    }

    public function test_with_role_state_assigns_role(): void
    {
        $user = User::factory()->withRole('admin')->create();

        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_factory_user_has_required_db_columns_filled(): void
    {
        // Migrace má `name` jako NOT NULL bez defaultu - reload z DB
        // by spadl, kdyby factory ho nevyplnil.
        $user = User::factory()->create();
        $reloaded = User::findOrFail($user->id);

        $this->assertNotEmpty($reloaded->name);
        $this->assertEquals(
            trim("{$reloaded->firstname} {$reloaded->lastname}"),
            $reloaded->name
        );
    }
}
