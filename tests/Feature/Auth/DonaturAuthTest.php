<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonaturAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_donatur_bisa_register(): void
    {
        $this->postJson('/api/v1/auth/donatur/register', [
            'name'                  => 'Budi',
            'email'                 => 'budi@test.id',
            'phone'                 => '081298765432',
            'password'              => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertCreated()->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@test.id',
            'role' => 'donatur',
        ]);
    }

    public function test_donatur_login_lalu_ambil_me(): void
    {
        User::factory()->create(['email' => 'budi@test.id', 'password' => 'rahasia123']);

        $token = $this->postJson('/api/v1/auth/donatur/login', [
            'email' => 'budi@test.id',
            'password' => 'rahasia123',
        ])->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('role', 'donatur');
    }

    public function test_ganti_password_lalu_login_dengan_yang_baru(): void
    {
        User::factory()->create(['email' => 'budi@test.id', 'password' => 'rahasia123']);
        $token = $this->loginDonatur('budi@test.id', 'rahasia123');

        $this->withToken($token)->putJson('/api/v1/auth/password', [
            'current_password'      => 'rahasia123',
            'password'              => 'rahasiaBaru123',
            'password_confirmation' => 'rahasiaBaru123',
        ])->assertOk();

        $this->postJson('/api/v1/auth/donatur/login', [
            'email' => 'budi@test.id',
            'password' => 'rahasiaBaru123',
        ])->assertOk();
    }

    public function test_logout_mencabut_token(): void
    {
        $user  = User::factory()->create(['email' => 'budi@test.id', 'password' => 'rahasia123']);
        $token = $this->loginDonatur('budi@test.id', 'rahasia123');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        // 1) Bukti nyata: token benar-benar terhapus dari database.
        $this->assertCount(0, $user->fresh()->tokens);

        // 2) Paksa guard melupakan user yang ter-cache di proses test,
        //    agar request berikutnya autentikasi ulang dari DB (mensimulasikan
        //    request HTTP baru di dunia nyata).
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_staff_tidak_bisa_login_donatur(): void
    {
        User::factory()->role(UserRole::Admin)->create([
            'email' => 'admin@test.id',
            'password' => 'Admin#2026',
        ]);

        $this->postJson('/api/v1/auth/donatur/login', [
            'email' => 'admin@test.id',
            'password' => 'Admin#2026',
        ])->assertStatus(403);
    }

    private function loginDonatur(string $email, string $password): string
    {
        return $this->postJson('/api/v1/auth/donatur/login', compact('email', 'password'))->json('token');
    }
}
