<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class StaffAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_tanpa_2fa_mengembalikan_setup_token(): void
    {
        User::factory()->role(UserRole::Admin)->create([
            'email' => 'admin@test.id',
            'password' => 'Admin#2026',
        ]);

        $this->postJson('/api/v1/auth/masuk-sistem', [
            'email' => 'admin@test.id',
            'password' => 'Admin#2026',
        ])->assertOk()
            ->assertJsonPath('status', '2fa_setup_required')
            ->assertJsonStructure(['setup_token']);
    }

    public function test_staff_bisa_setup_enable_2fa_dan_dapat_backup_codes(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create([
            'email' => 'admin@test.id',
            'password' => 'Admin#2026',
        ]);

        $setupToken = $this->postJson('/api/v1/auth/masuk-sistem', [
            'email' => 'admin@test.id',
            'password' => 'Admin#2026',
        ])->json('setup_token');

        $secret = $this->withToken($setupToken)
            ->getJson('/api/v1/auth/2fa/setup')
            ->assertOk()
            ->json('secret');

        $otp = (new Google2FA)->getCurrentOtp($secret);

        $res = $this->withToken($setupToken)
            ->postJson('/api/v1/auth/2fa/enable', ['code' => $otp])
            ->assertOk()
            ->assertJsonStructure(['backup_codes', 'token']);

        $this->assertCount(8, $res->json('backup_codes'));
        $this->assertTrue($admin->refresh()->two_factor_enabled);
    }

    public function test_login_dengan_2fa_aktif_butuh_challenge(): void
    {
        $admin  = $this->staffWith2fa();
        $secret = $admin->two_factor_secret;

        $challenge = $this->postJson('/api/v1/auth/masuk-sistem', [
            'email' => $admin->email,
            'password' => 'Admin#2026',
        ])->assertOk()
            ->assertJsonPath('status', '2fa_required')
            ->json('challenge_token');

        $otp = (new Google2FA)->getCurrentOtp($secret);

        $this->withToken($challenge)
            ->postJson('/api/v1/auth/masuk-sistem/2fa', ['code' => $otp])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_backup_code_hanya_bisa_sekali(): void
    {
        $svc   = app(TwoFactorService::class);
        $codes = $svc->generateBackupCodes();
        $admin = $this->staffWith2fa($codes);

        $challenge1 = $this->postJson('/api/v1/auth/masuk-sistem', [
            'email' => $admin->email,
            'password' => 'Admin#2026',
        ])->json('challenge_token');

        $this->withToken($challenge1)
            ->postJson('/api/v1/auth/masuk-sistem/2fa', ['code' => $codes[0]])
            ->assertOk();

        $challenge2 = $this->postJson('/api/v1/auth/masuk-sistem', [
            'email' => $admin->email,
            'password' => 'Admin#2026',
        ])->json('challenge_token');

        $this->withToken($challenge2)
            ->postJson('/api/v1/auth/masuk-sistem/2fa', ['code' => $codes[0]])
            ->assertStatus(422);
    }

    public function test_kredensial_salah_ditolak(): void
    {
        User::factory()->role(UserRole::Admin)->create([
            'email' => 'admin@test.id',
            'password' => 'Admin#2026',
        ]);

        $this->postJson('/api/v1/auth/masuk-sistem', [
            'email' => 'admin@test.id',
            'password' => 'salah',
        ])->assertStatus(401);
    }

    public function test_donatur_tidak_bisa_login_staff(): void
    {
        User::factory()->create([
            'email' => 'donatur@test.id',
            'password' => 'rahasia123',
        ]);

        $this->postJson('/api/v1/auth/masuk-sistem', [
            'email' => 'donatur@test.id',
            'password' => 'rahasia123',
        ])->assertStatus(403);
    }

    /** Helper: buat staff yang 2FA-nya sudah aktif. */
    private function staffWith2fa(?array $backupCodes = null): User
    {
        $svc    = app(TwoFactorService::class);
        $secret = $svc->generateSecret();
        $codes  = $backupCodes ?? $svc->generateBackupCodes();

        return User::factory()->role(UserRole::Admin)->create([
            'email'                     => 'admin2fa@test.id',
            'password'                  => 'Admin#2026',
            'two_factor_secret'         => $secret,
            'two_factor_enabled'        => true,
            'two_factor_confirmed_at'   => now(),
            'two_factor_recovery_codes' => json_encode($svc->hashBackupCodes($codes)),
        ]);
    }
}
