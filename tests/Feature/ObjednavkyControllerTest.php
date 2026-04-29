<?php
// tests/Feature/ObjednavkyControllerTest.php

namespace Tests\Feature;

use App\Models\DruhOopp;
use App\Models\Objednavka;
use App\Models\Produkt;
use App\Models\User;
use App\Models\Zamestnanec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ObjednavkyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Zamestnanec $zamestnanec;
    protected Produkt $produkt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->withRole('admin')->create();

        $this->zamestnanec = Zamestnanec::create([
            'jmeno'     => 'Jan',
            'prijmeni'  => 'Novák',
            'stredisko' => 'P422',
        ]);

        $druh = DruhOopp::create(['nazev' => 'Boty']);
        $this->produkt = Produkt::create([
            'nazev'              => 'Pracovní obuv',
            'druh_id'            => $druh->id,
            'dostupne_velikosti' => '40,41,42,43,44',
            'cena'               => 1200.00,
        ]);

        $this->actingAsSession($this->user);
        Storage::fake('local');
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

    public function test_can_create_order(): void
    {
        Notification::fake();

        $response = $this->postJson('/odeslat-objednavku', [
            'zamestnanec_id' => $this->zamestnanec->id,
            'produkt_id'     => $this->produkt->id,
            'velikost'       => '42',
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('objednavky', [
            'zamestnanec_id' => $this->zamestnanec->id,
            'produkt_id'     => $this->produkt->id,
            'velikost'       => '42',
            'status'         => 'cekajici',
        ]);
    }

    public function test_create_order_requires_valid_zamestnanec(): void
    {
        $this->postJson('/odeslat-objednavku', [
            'zamestnanec_id' => 999999,
            'produkt_id'     => $this->produkt->id,
            'velikost'       => '42',
        ])->assertStatus(422);
    }

    public function test_create_order_requires_valid_produkt(): void
    {
        $this->postJson('/odeslat-objednavku', [
            'zamestnanec_id' => $this->zamestnanec->id,
            'produkt_id'     => 999999,
            'velikost'       => '42',
        ])->assertStatus(422);
    }

    public function test_can_mark_order_as_objednano(): void
    {
        $order = Objednavka::create([
            'zamestnanec_id'  => $this->zamestnanec->id,
            'produkt_id'      => $this->produkt->id,
            'velikost'        => '42',
            'datum_objednani' => now()->toDateString(),
            'status'          => 'cekajici',
        ]);

        $this->postJson('/objednat', ['order_id' => $order->id])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertEquals('Objednano', $order->fresh()->status);
    }

    public function test_can_delete_order(): void
    {
        $order = Objednavka::create([
            'zamestnanec_id'  => $this->zamestnanec->id,
            'produkt_id'      => $this->produkt->id,
            'velikost'        => '42',
            'datum_objednani' => now()->toDateString(),
            'status'          => 'cekajici',
        ]);

        $this->postJson('/delete', ['order_id' => $order->id])
            ->assertOk();

        $this->assertDatabaseMissing('objednavky', ['id' => $order->id]);
    }

    public function test_vydat_with_valid_signature_succeeds(): void
    {
        $order = Objednavka::create([
            'zamestnanec_id'  => $this->zamestnanec->id,
            'produkt_id'      => $this->produkt->id,
            'velikost'        => '42',
            'datum_objednani' => now()->toDateString(),
            'status'          => 'Objednano',
        ]);

        // Validní 100x50 PNG (transparent)
        $signature = 'data:image/png;base64,' . $this->validPngBase64(100, 50);

        $response = $this->postJson('/vydat', [
            'order_id'  => $order->id,
            'signature' => $signature,
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);

        $fresh = $order->fresh();
        $this->assertEquals('vydano', $fresh->status);
        $this->assertNotNull($fresh->podpis_path);
        $this->assertNotNull($fresh->datum_vydani);

        Storage::disk('local')->assertExists('signatures/' . $fresh->podpis_path);
    }

    public function test_vydat_rejects_invalid_signature_format(): void
    {
        $order = Objednavka::create([
            'zamestnanec_id'  => $this->zamestnanec->id,
            'produkt_id'      => $this->produkt->id,
            'velikost'        => '42',
            'datum_objednani' => now()->toDateString(),
            'status'          => 'Objednano',
        ]);

        $this->postJson('/vydat', [
            'order_id'  => $order->id,
            'signature' => 'not-a-valid-signature',
        ])->assertStatus(400);

        $this->assertEquals('Objednano', $order->fresh()->status,
            'Status se nesmí změnit při neplatném podpisu');
    }

    public function test_vydat_rejects_already_issued_order(): void
    {
        $order = Objednavka::create([
            'zamestnanec_id'  => $this->zamestnanec->id,
            'produkt_id'      => $this->produkt->id,
            'velikost'        => '42',
            'datum_objednani' => now()->toDateString(),
            'status'          => 'vydano',  // už vydáno
            'podpis_path'     => 'old.png',
        ]);

        $signature = 'data:image/png;base64,' . $this->validPngBase64(100, 50);

        $this->postJson('/vydat', [
            'order_id'  => $order->id,
            'signature' => $signature,
        ])->assertStatus(400);

        $this->assertEquals('old.png', $order->fresh()->podpis_path,
            'Druhé vydání nesmí přepsat původní podpis');
    }

    public function test_vydat_rejects_oversized_signature(): void
    {
        $order = Objednavka::create([
            'zamestnanec_id'  => $this->zamestnanec->id,
            'produkt_id'      => $this->produkt->id,
            'velikost'        => '42',
            'datum_objednani' => now()->toDateString(),
            'status'          => 'Objednano',
        ]);

        // 600 000 znaků - víc než MAX_SIGNATURE_LENGTH (500 000)
        $signature = 'data:image/png;base64,' . str_repeat('A', 600_000);

        // Validace v Request::validate spadne se 422 (size constraint)
        $this->postJson('/vydat', [
            'order_id'  => $order->id,
            'signature' => $signature,
        ])->assertStatus(422);
    }

    /**
     * Vyrobí validní PNG určité velikosti (base64 data bez prefixu).
     */
    private function validPngBase64(int $width, int $height): string
    {
        $img = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return base64_encode($bytes);
    }
}
