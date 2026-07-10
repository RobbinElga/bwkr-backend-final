<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendWhatsAppNotification;
use App\Models\BankAccount;
use App\Models\DonationInput;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DonationInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_publik_bisa_submit_donasi(): void
    {
        Queue::fake();
        Storage::fake('local');
        $bank = BankAccount::factory()->create();

        $res = $this->postJson('/api/v1/donations', [
            'donor_name'      => 'Budi',
            'donor_phone'     => '081234567890',
            'amount'          => 500000,
            'bank_account_id' => $bank->id,
            'proof'           => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertCreated()->assertJsonPath('status', 'pending');

        $this->assertNotEmpty($res->json('ref_no'));

        $donation = DonationInput::first();
        Storage::disk('local')->assertExists($donation->proof_file);  // bukti di disk PRIVAT
        $this->assertSame('online', $donation->source->value);
        $this->assertNull($donation->input_by);
    }

    public function test_nomor_hp_terenkripsi_dan_hash_terisi(): void
    {
        Queue::fake();
        Storage::fake('local');
        $bank = BankAccount::factory()->create();

        $this->postJson('/api/v1/donations', [
            'donor_name'      => 'Budi',
            'donor_phone'     => '081234567890',
            'amount'          => 100000,
            'bank_account_id' => $bank->id,
            'proof'           => UploadedFile::fake()->image('b.jpg'),
        ])->assertCreated();

        $raw = DB::table('donations_input')->first();
        $this->assertNotSame('081234567890', $raw->donor_phone);     // terenkripsi
        $this->assertNotNull($raw->donor_phone_hash);                // hash terisi
    }

    public function test_donatur_login_donasi_terhubung_ke_akun(): void
    {
        Queue::fake();
        Storage::fake('local');
        $bank  = BankAccount::factory()->create();
        $donor = User::factory()->create();   // role donatur
        Sanctum::actingAs($donor, ['donatur']);

        $this->postJson('/api/v1/donations', [
            'donor_name'      => 'Budi',
            'donor_phone'     => '0812',
            'amount'          => 100000,
            'bank_account_id' => $bank->id,
            'proof'           => UploadedFile::fake()->image('b.jpg'),
        ])->assertCreated();

        $this->assertSame($donor->id, DonationInput::first()->user_id);
    }

    public function test_cek_status_by_ref_no(): void
    {
        $d = DonationInput::factory()->create(['donor_name' => 'Budi']);

        $this->getJson("/api/v1/donations/{$d->ref_no}/status")
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('ref_no', $d->ref_no);
    }

    public function test_cs_bisa_input_manual(): void
    {
        Queue::fake();
        Storage::fake('local');
        Sanctum::actingAs(User::factory()->role(UserRole::Cs)->create(), ['staff']);
        $bank = BankAccount::factory()->create();

        $this->postJson('/api/v1/admin/donations-input', [
            'donor_name'      => 'Donatur Offline',
            'donor_phone'     => '081200001111',
            'amount'          => 250000,
            'bank_account_id' => $bank->id,
            'proof'           => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertCreated()->assertJsonPath('data.source', 'manual');

        $this->assertNotNull(DonationInput::first()->input_by);
    }

    public function test_admin_cari_donasi_by_nomor(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
        DonationInput::factory()->create(['donor_phone' => '081234567890']);
        DonationInput::factory()->create(['donor_phone' => '081299998888']);

        $res = $this->getJson('/api/v1/admin/donations-input?phone=081234567890')->assertOk();
        $this->assertCount(1, $res->json('data'));
    }

    public function test_admin_bisa_stream_bukti(): void
    {
        Storage::fake('local');
        $donation = DonationInput::factory()->create();
        Storage::disk('local')->put('proofs/test.webp', 'dummy');
        $donation->proof_file = 'proofs/test.webp';
        $donation->save();

        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
        $this->getJson("/api/v1/admin/donations-input/{$donation->id}/proof")->assertOk();
    }

    public function test_cs_bisa_input_manual_dengan_tanggal(): void
    {
        Queue::fake();
        Storage::fake('local');
        Sanctum::actingAs(User::factory()->role(UserRole::Cs)->create(), ['staff']);
        $bank = BankAccount::factory()->create();

        $this->postJson('/api/v1/admin/donations-input', [
            'donor_name'      => 'Donatur Offline',
            'donor_phone'     => '081200001111',
            'amount'          => 250000,
            'bank_account_id' => $bank->id,
            'proof'           => UploadedFile::fake()->image('bukti.jpg'),
            'donation_date'   => '2026-06-15',
        ])->assertCreated()->assertJsonPath('data.donation_date', '2026-06-15');
    }

    public function test_donatur_tidak_bisa_akses_donations_input(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/donations-input')->assertStatus(403);
    }
}
