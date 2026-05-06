<?php
// tests/Feature/LekarnickyMaterialTest.php

namespace Tests\Feature;

use App\Models\Lekarnicky;
use App\Models\LekarnickeMaterial;
use App\Models\LekarnickyMaterialObjednavka;
use App\Models\Uraz;
use App\Models\User;
use App\Models\VydejMaterialu;
use App\Models\Zamestnanec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LekarnickyMaterialTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Lekarnicky $lekarnicka;
    protected Zamestnanec $zamestnanec;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->withRole('super_admin')->create();
        $this->actingAsSession($this->admin);

        $this->lekarnicka = Lekarnicky::create([
            'nazev'    => 'Hlavní lékárnička',
            'umisteni' => 'Přízemí',
            'zodpovedna_osoba' => 'Test',
            'status'   => 'aktivni',
        ]);

        $this->zamestnanec = Zamestnanec::create([
            'jmeno' => 'Jan', 'prijmeni' => 'Novák', 'stredisko' => 'P422',
        ]);
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

    public function test_can_create_material(): void
    {
        $response = $this->postJson("/api/lekarnicke/{$this->lekarnicka->id}/material", [
            'nazev_materialu' => 'Náplast',
            'typ_materialu'   => 'naplast',
            'aktualni_pocet'  => 50,
            'minimalni_pocet' => 10,
            'maximalni_pocet' => 100,
            'jednotka'        => 'ks',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('lekarnicky_material', [
            'lekarnicky_id'  => $this->lekarnicka->id,
            'nazev_materialu' => 'Náplast',
            'aktualni_pocet'  => 50,
        ]);
    }

    public function test_vydej_material_decrements_stock(): void
    {
        $material = LekarnickeMaterial::create([
            'lekarnicky_id'   => $this->lekarnicka->id,
            'nazev_materialu' => 'Obvaz',
            'typ_materialu'   => 'naplast',
            'aktualni_pocet'  => 20,
            'minimalni_pocet' => 5,
            'maximalni_pocet' => 50,
            'jednotka'        => 'ks',
        ]);

        $uraz = Uraz::create([
            'zamestnanec_id'         => $this->zamestnanec->id,
            'lekarnicky_id'          => $this->lekarnicka->id,
            'datum_cas_urazu'        => now()->subHour(),
            'popis_urazu'            => 'Říznutí do prstu při manipulaci',
            'misto_urazu'            => 'Sklad',
            'zavaznost'              => 'lehky',
            'poskytnute_osetreni'    => 'Obvaz',
            'osoba_poskytujici_pomoc'=> 'Vedoucí směny',
        ]);

        $response = $this->postJson('/api/lekarnicke/vydej', [
            'uraz_id'          => $uraz->id,
            'material_id'      => $material->id,
            'vydane_mnozstvi'  => 5,
            'osoba_vydavajici' => 'Skladník',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(15, $material->fresh()->aktualni_pocet);
        $this->assertDatabaseHas('vydej_materialu', [
            'uraz_id'         => $uraz->id,
            'material_id'     => $material->id,
            'vydane_mnozstvi' => 5,
        ]);
    }

    public function test_responsible_user_sees_only_assigned_lekarnicky(): void
    {
        $other = Lekarnicky::create([
            'nazev'    => 'Vedlejší lékárnička',
            'umisteni' => 'Sklad',
            'zodpovedna_osoba' => 'Někdo jiný',
            'status'   => 'aktivni',
        ]);

        $user = User::factory()->withRole('lekarnicky_user')->create();
        $user->lekarnickAccess()->attach($this->lekarnicka->id, ['access_level' => 'admin']);
        $this->actingAsSession($user);

        $response = $this->getJson('/api/lekarnicke/dashboard');

        $response->assertOk();
        $ids = collect($response->json('lekarnicke'))->pluck('id');

        $this->assertTrue($ids->contains($this->lekarnicka->id));
        $this->assertFalse($ids->contains($other->id));
    }

    public function test_vydej_can_create_order_and_doplnit_closes_it(): void
    {
        $material = LekarnickeMaterial::create([
            'lekarnicky_id'   => $this->lekarnicka->id,
            'nazev_materialu' => 'Sterilní obvaz',
            'typ_materialu'   => 'obvaz',
            'aktualni_pocet'  => 20,
            'minimalni_pocet' => 5,
            'maximalni_pocet' => 50,
            'jednotka'        => 'ks',
        ]);

        $user = User::factory()->withRole('lekarnicky_user')->create();
        $user->lekarnickAccess()->attach($this->lekarnicka->id, ['access_level' => 'admin']);
        $this->actingAsSession($user);

        $this->postJson('/api/lekarnicke/vydej', [
            'material_id' => $material->id,
            'vydane_mnozstvi' => 4,
            'objednat_po_vydeji' => true,
        ])->assertOk()->assertJson(['success' => true]);

        $objednavka = LekarnickyMaterialObjednavka::where('material_id', $material->id)->firstOrFail();
        $this->assertSame(16, $material->fresh()->aktualni_pocet);
        $this->assertSame(4, $objednavka->mnozstvi);
        $this->assertSame('cekajici', $objednavka->status);

        $this->actingAsSession($this->admin);
        $this->patchJson("/api/lekarnicke/objednavky-materialu/{$objednavka->id}/status", [
            'status' => 'vydano',
        ])->assertOk();

        $this->actingAsSession($user);
        $this->postJson("/api/lekarnicke/material/{$material->id}/doplnit", [
            'objednavka_id' => $objednavka->id,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(20, $material->fresh()->aktualni_pocet);
        $this->assertSame('doplneno', $objednavka->fresh()->status);
    }

    public function test_vydej_rejects_when_insufficient_stock(): void
    {
        $material = LekarnickeMaterial::create([
            'lekarnicky_id'   => $this->lekarnicka->id,
            'nazev_materialu' => 'Obvaz',
            'typ_materialu'   => 'naplast',
            'aktualni_pocet'  => 3,
            'minimalni_pocet' => 5,
            'maximalni_pocet' => 50,
            'jednotka'        => 'ks',
        ]);

        $uraz = Uraz::create([
            'zamestnanec_id'         => $this->zamestnanec->id,
            'lekarnicky_id'          => $this->lekarnicka->id,
            'datum_cas_urazu'        => now()->subHour(),
            'popis_urazu'            => 'Říznutí do prstu při manipulaci',
            'misto_urazu'            => 'Sklad',
            'zavaznost'              => 'lehky',
            'poskytnute_osetreni'    => 'Obvaz',
            'osoba_poskytujici_pomoc'=> 'Vedoucí',
        ]);

        $response = $this->postJson('/api/lekarnicke/vydej', [
            'uraz_id'          => $uraz->id,
            'material_id'      => $material->id,
            'vydane_mnozstvi'  => 10,  // víc než je skladem
            'osoba_vydavajici' => 'Skladník',
        ]);

        $response->assertStatus(400)->assertJson(['success' => false]);

        // Stav se nesmí změnit
        $this->assertEquals(3, $material->fresh()->aktualni_pocet);
        $this->assertEquals(0, VydejMaterialu::count());
    }

    public function test_kontrola_updates_dates(): void
    {
        $response = $this->postJson("/api/lekarnicke/{$this->lekarnicka->id}/kontrola");

        $response->assertOk()->assertJson(['success' => true]);

        $fresh = $this->lekarnicka->fresh();
        $this->assertNotNull($fresh->posledni_kontrola);
        $this->assertNotNull($fresh->dalsi_kontrola);
        $this->assertTrue($fresh->dalsi_kontrola->isFuture());
    }

    public function test_uraz_requires_min_description_length(): void
    {
        $this->postJson('/api/lekarnicke/urazy', [
            'zamestnanec_id'         => $this->zamestnanec->id,
            'lekarnicky_id'          => $this->lekarnicka->id,
            'datum_cas_urazu'        => now()->subHour()->format('Y-m-d H:i:s'),
            'popis_urazu'            => 'krátké',  // pod 10 znaků
            'misto_urazu'            => 'Sklad',
            'zavaznost'              => 'lehky',
            'poskytnute_osetreni'    => 'Obvaz',
            'osoba_poskytujici_pomoc'=> 'Vedoucí',
        ])->assertStatus(422);
    }

    public function test_uraz_rejects_future_date(): void
    {
        $this->postJson('/api/lekarnicke/urazy', [
            'zamestnanec_id'         => $this->zamestnanec->id,
            'lekarnicky_id'          => $this->lekarnicka->id,
            'datum_cas_urazu'        => now()->addDay()->format('Y-m-d H:i:s'),
            'popis_urazu'            => 'Toto je validní popis úrazu o dostatečné délce',
            'misto_urazu'            => 'Sklad',
            'zavaznost'              => 'lehky',
            'poskytnute_osetreni'    => 'Obvaz aplikován',
            'osoba_poskytujici_pomoc'=> 'Vedoucí',
        ])->assertStatus(422);
    }
}
