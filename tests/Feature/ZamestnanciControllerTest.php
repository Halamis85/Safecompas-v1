<?php
// tests/Feature/ZamestnanciControllerTest.php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zamestnanec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZamestnanciControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->withRole('admin')->create();
        $this->actingAsSession($this->admin);
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
                'is_admin'        => true,
            ],
            'last_activity' => time(),
        ]);
        return $this;
    }

    public function test_can_list_employees(): void
    {
        Zamestnanec::create(['jmeno' => 'Jan', 'prijmeni' => 'Novák', 'stredisko' => 'P422']);
        Zamestnanec::create(['jmeno' => 'Petr', 'prijmeni' => 'Svoboda', 'stredisko' => 'P450']);

        $response = $this->getJson('/employee');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_can_create_employee(): void
    {
        $response = $this->postJson('/employeeAdd', [
            'jmeno'     => 'Jan',
            'prijmeni'  => 'Novák',
            'stredisko' => 'P422',
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);
        $this->assertDatabaseHas('zamestnanci', [
            'jmeno' => 'Jan', 'prijmeni' => 'Novák', 'stredisko' => 'P422',
        ]);
    }

    public function test_create_employee_requires_all_fields(): void
    {
        $this->postJson('/employeeAdd', ['jmeno' => 'Jan'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['prijmeni', 'stredisko']);
    }

    public function test_can_delete_employee(): void
    {
        $z = Zamestnanec::create([
            'jmeno' => 'Jan', 'prijmeni' => 'Novák', 'stredisko' => 'P422',
        ]);

        $this->deleteJson("/employee/{$z->id}")->assertOk();
        $this->assertDatabaseMissing('zamestnanci', ['id' => $z->id]);
    }

    public function test_delete_nonexistent_employee_returns_404(): void
    {
        $this->deleteJson('/employee/999999')->assertStatus(404);
    }

    public function test_search_finds_by_jmeno_or_prijmeni(): void
    {
        Zamestnanec::create(['jmeno' => 'Jan', 'prijmeni' => 'Novák', 'stredisko' => 'P422']);
        Zamestnanec::create(['jmeno' => 'Petr', 'prijmeni' => 'Svoboda', 'stredisko' => 'P450']);
        Zamestnanec::create(['jmeno' => 'Karel', 'prijmeni' => 'Janek', 'stredisko' => 'P422']);

        $response = $this->getJson('/zamestnanci?q=Jan');

        $response->assertOk();
        $names = collect($response->json())->pluck('jmeno')->toArray();
        $this->assertContains('Jan', $names);
        $this->assertContains('Karel', $names); // má prijmeni "Janek"
    }
}
