<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendWhatsAppNotification;
use App\Models\DonationInput;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_daftar_donatur_teragregasi(): void
    {
        $this->actingAsAdmin();
        DonationInput::factory()->create(['donor_phone' => '081111111111', 'donor_name' => 'Budi', 'amount' => 100000]);
        DonationInput::factory()->create(['donor_phone' => '081111111111', 'donor_name' => 'Budi', 'amount' => 200000]);
        DonationInput::factory()->create(['donor_phone' => '082222222222', 'donor_name' => 'Andi', 'amount' => 50000]);

        $res = $this->getJson('/api/v1/admin/crm/donors')->assertOk();

        $this->assertCount(2, $res->json('data'));   // 2 donatur unik
        $budi = collect($res->json('data'))->firstWhere('name', 'Budi');
        $this->assertSame(300000, $budi['total_donated']);
    }

    public function test_ubah_tier_premium(): void
    {
        $this->actingAsAdmin();
        DonationInput::factory()->create(['donor_phone' => '081111111111']);
        $hash = DonationInput::hashPhone('081111111111');

        $this->putJson("/api/v1/admin/crm/donors/{$hash}/tier", ['tier' => 'premium'])
            ->assertOk()->assertJsonPath('tier', 'premium');

        $this->assertDatabaseHas('donor_profiles', ['donor_phone_hash' => $hash, 'tier' => 'premium']);
    }

    public function test_broadcast_kirim_wa_dengan_placeholder_dan_catat_log(): void
    {
        Queue::fake();
        $this->actingAsAdmin();
        DonationInput::factory()->create(['donor_phone' => '081111111111', 'donor_name' => 'Budi', 'amount' => 300000]);

        $this->postJson('/api/v1/admin/crm/broadcast', [
            'message' => 'Halo [Nama], total donasi Anda [Nominal].',
        ])->assertOk()->assertJsonPath('sent', 1);

        Queue::assertPushed(SendWhatsAppNotification::class);
        $this->assertDatabaseHas('crm_logs', [
            'message' => 'Halo Budi, total donasi Anda Rp300.000.',
        ]);
    }

    public function test_broadcast_filter_tier_premium(): void
    {
        Queue::fake();
        $this->actingAsAdmin();
        DonationInput::factory()->create(['donor_phone' => '081111111111', 'amount' => 100000]); // reguler
        DonationInput::factory()->create(['donor_phone' => '082222222222', 'amount' => 100000]); // jadi premium
        $hashPremium = DonationInput::hashPhone('082222222222');
        $this->putJson("/api/v1/admin/crm/donors/{$hashPremium}/tier", ['tier' => 'premium'])->assertOk();

        $this->postJson('/api/v1/admin/crm/broadcast', [
            'message' => 'Khusus premium.',
            'tier'    => 'premium',
        ])->assertOk()->assertJsonPath('sent', 1);   // hanya 1 premium
    }

    public function test_template_crud(): void
    {
        $this->actingAsAdmin();
        $this->postJson('/api/v1/admin/crm/templates', [
            'name' => 'Ucapan',
            'content' => 'Halo [Nama]',
        ])->assertCreated()->assertJsonPath('data.name', 'Ucapan');
    }

    public function test_donatur_diblokir_dari_crm(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/crm/donors')->assertStatus(403);
    }
}
