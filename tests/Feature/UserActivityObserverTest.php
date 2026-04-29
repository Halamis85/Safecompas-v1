<?php
// tests/Feature/UserActivityObserverTest.php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserActivity;
use App\Models\Zamestnanec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityObserverTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // Vytvoříme admina a "přihlásíme" ho do session
        // (observer čte session('user.id') jako reference na uživatele).
        // Vytvoření samotného admina vyvolá audit (created), proto ho
        // v testu počítáme nebo mažeme záznamy předtím, než testujeme.
        $this->admin = User::factory()->withRole('admin')->create();

        session([
            'user' => [
                'id'             => $this->admin->id,
                'username'       => $this->admin->username,
                'is_super_admin' => false,
            ],
        ]);

        // Vyčistíme audit záznamy ze setUp - testy budeme dělat na čistém stavu
        UserActivity::truncate();
    }

    public function test_create_logs_activity(): void
    {
        Zamestnanec::create([
            'jmeno'     => 'Jan',
            'prijmeni'  => 'Novák',
            'stredisko' => 'P422',
        ]);

        $activity = UserActivity::first();

        $this->assertNotNull($activity);
        $this->assertEquals('created', $activity->action);
        $this->assertEquals('zamestnanci', $activity->table_name);
        $this->assertEquals($this->admin->id, $activity->user_id);
        $this->assertEquals('Jan', $activity->new_values['jmeno']);
    }

    public function test_update_logs_only_significant_changes(): void
    {
        $z = Zamestnanec::create([
            'jmeno' => 'Jan', 'prijmeni' => 'Novák', 'stredisko' => 'P422',
        ]);
        UserActivity::truncate();

        $z->update(['stredisko' => 'P450']);

        $activity = UserActivity::where('action', 'updated')->first();

        $this->assertNotNull($activity);
        $this->assertEquals('zamestnanci', $activity->table_name);
        $this->assertEquals('P422', $activity->old_values['stredisko']);
        $this->assertEquals('P450', $activity->new_values['stredisko']);
        // Stredisko je jediná business změna - jiné klíče tam být nesmí
        $this->assertArrayNotHasKey('updated_at', $activity->new_values);
    }

    public function test_update_with_only_timestamp_change_does_not_log(): void
    {
        $z = Zamestnanec::create([
            'jmeno' => 'Jan', 'prijmeni' => 'Novák', 'stredisko' => 'P422',
        ]);
        UserActivity::truncate();

        // Touch změní jen updated_at - to by se nemělo logovat
        $z->touch();

        $this->assertEquals(0, UserActivity::where('action', 'updated')->count());
    }

    public function test_delete_logs_old_values(): void
    {
        $z = Zamestnanec::create([
            'jmeno' => 'Jan', 'prijmeni' => 'Novák', 'stredisko' => 'P422',
        ]);
        UserActivity::truncate();

        $z->delete();

        $activity = UserActivity::where('action', 'deleted')->first();

        $this->assertNotNull($activity);
        $this->assertEquals('Jan', $activity->old_values['jmeno']);
        $this->assertNull($activity->new_values);
    }

    public function test_password_is_not_persisted_in_audit(): void
    {
        $user = User::factory()->create();
        $audit = UserActivity::where('table_name', 'users')
            ->where('action', 'created')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit, 'Audit User::create má vzniknout');
        $this->assertArrayNotHasKey('password', $audit->new_values ?? []);
        $this->assertArrayNotHasKey('remember_token', $audit->new_values ?? []);
    }

    public function test_user_password_change_does_not_leak_to_audit(): void
{
    $user = User::factory()->create();
    UserActivity::truncate();

    $user->update(['password' => bcrypt('NoveSuperHeslo123!')]);

    $audit = UserActivity::where('action', 'updated')
        ->where('table_name', 'users')
        ->first();

    if ($audit !== null) {
        // Pokud audit vznikl, hlavně nesmí obsahovat heslo
        $this->assertArrayNotHasKey('password', $audit->new_values ?? []);
    } else {
        // Update jen hesla → po odfiltrování citlivých polí žádná
        // významná změna zbytek → observer záznam právem neuložil.
        // To je správné chování.
        $this->assertTrue(true, 'Update jen hesla nevytvořil audit (správně)');
    }
}

    public function test_no_audit_when_no_session_user(): void
    {
        session()->forget('user');

        Zamestnanec::create([
            'jmeno' => 'Petr', 'prijmeni' => 'Svoboda', 'stredisko' => 'P422',
        ]);

        $this->assertEquals(0, UserActivity::count(),
            'Bez session user nesmí vzniknout audit záznam (chrání před chybou user_id NOT NULL)');
    }

    public function test_audit_failure_does_not_block_main_operation(): void
    {
        // Audit selže pokud user_id ukazuje na neexistujícího uživatele
        // (FK constraint). Hlavní create musí proběhnout i tak.
        session(['user' => ['id' => 999999, 'username' => 'ghost']]);

        $z = Zamestnanec::create([
            'jmeno' => 'Audit', 'prijmeni' => 'Failure', 'stredisko' => 'P422',
        ]);

        // Hlavní operace prošla
        $this->assertDatabaseHas('zamestnanci', ['id' => $z->id]);
        // Audit naopak NEvznikl
        $this->assertEquals(0, UserActivity::count());
    }
}
