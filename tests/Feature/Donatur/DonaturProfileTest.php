<?php

namespace Tests\Feature\Donatur;

use App\Enums\UserRole;
use App\Models\DonationInput;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DonaturProfileTest extends TestCase
{
    use RefreshDatabase;

    private function donatur(): User
    {
        return User::factory()->create(['phone' => '081234567890']);   // role donatur default
    }

    public function test_lihat_profil(): void
    {
        $u = $this->donatur();
        Sanctum::actingAs($u, ['donatur']);

        $this->getJson('/api/v1/donatur/profile')
            ->assertOk()->assertJsonPath('data.email', $u->email);
    }

    public function test_ubah_profil(): void
    {
        Sanctum::actingAs($this->donatur(), ['donatur']);

        $this->putJson('/api/v1/donatur/profile', ['name' => 'Budi Baru'])
            ->assertOk()->assertJsonPath('data.name', 'Budi Baru');
    }

    public function test_riwayat_termasuk_donasi_tamu_dengan_hp_sama(): void
    {
        $u = $this->donatur();
        Sanctum::actingAs($u, ['donatur']);

        DonationInput::factory()->create(['user_id' => $u->id]);                              // donasi login
        DonationInput::factory()->create(['user_id' => null, 'donor_phone' => '081234567890']); // tamu, HP sama
        DonationInput::factory()->create(['user_id' => null, 'donor_phone' => '089999999999']); // orang lain

        $res = $this->getJson('/api/v1/donatur/donations')->assertOk();
        $this->assertCount(2, $res->json('data'));
    }

    public function test_lihat_detail_donasi_sendiri(): void
    {
        $u = $this->donatur();
        Sanctum::actingAs($u, ['donatur']);
        $d = DonationInput::factory()->create(['user_id' => $u->id]);

        $this->getJson("/api/v1/donatur/donations/{$d->ref_no}")
            ->assertOk()->assertJsonPath('data.ref_no', $d->ref_no);
    }

    public function test_tidak_bisa_lihat_donasi_orang_lain(): void
    {
        Sanctum::actingAs($this->donatur(), ['donatur']);
        $other = DonationInput::factory()->create(['user_id' => null, 'donor_phone' => '089999999999']);

        $this->getJson("/api/v1/donatur/donations/{$other->ref_no}")->assertNotFound();
    }

    public function test_staff_diblokir_dari_area_donatur(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
        $this->getJson('/api/v1/donatur/profile')->assertStatus(403);
    }
}
