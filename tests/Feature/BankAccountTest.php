<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BankAccountTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_admin_buat_rekening_dan_nomor_terenkripsi(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/admin/bank-accounts', [
            'bank_name'      => 'BSI',
            'account_number' => '1234567890',
            'account_name'   => 'Yayasan BWKR',
        ])->assertCreated()->assertJsonPath('data.account_number', '1234567890');

        // di DB tersimpan terenkripsi (raw != plaintext)
        $raw = DB::table('bank_accounts')->value('account_number');
        $this->assertNotSame('1234567890', $raw);
    }

    public function test_publik_hanya_lihat_rekening_aktif(): void
    {
        BankAccount::factory()->create(['is_active' => true]);
        BankAccount::factory()->create(['is_active' => false]);

        $res = $this->getJson('/api/v1/bank-accounts')->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertArrayHasKey('account_number', $res->json('data.0')); // tampil utk transfer
    }

    public function test_admin_ubah_rekening(): void
    {
        $this->actingAsAdmin();
        $acc = BankAccount::factory()->create(['bank_name' => 'Lama']);

        $this->putJson("/api/v1/admin/bank-accounts/{$acc->id}", ['bank_name' => 'Baru'])
            ->assertOk()->assertJsonPath('data.bank_name', 'Baru');
    }

    public function test_admin_hapus_rekening(): void
    {
        $this->actingAsAdmin();
        $acc = BankAccount::factory()->create();

        $this->deleteJson("/api/v1/admin/bank-accounts/{$acc->id}")->assertOk();
        $this->assertSoftDeleted('bank_accounts', ['id' => $acc->id]);
    }

    public function test_donatur_tidak_bisa_akses_admin_rekening(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/bank-accounts')->assertStatus(403);
    }
} 
