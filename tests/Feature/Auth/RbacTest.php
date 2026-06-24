<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bisa_akses_admin_ping(): void
    {
        $this->withToken($this->staffToken(UserRole::Admin))
            ->getJson('/api/v1/admin/ping')->assertOk();
    }

    public function test_admin_tidak_bisa_akses_super_only(): void
    {
        $this->withToken($this->staffToken(UserRole::Admin))
            ->getJson('/api/v1/admin/ping-super')->assertStatus(403);
    }

    public function test_super_admin_bisa_akses_super_only(): void
    {
        $this->withToken($this->staffToken(UserRole::SuperAdmin))
            ->getJson('/api/v1/admin/ping-super')->assertOk();
    }

    public function test_token_donatur_diblokir_dari_area_admin(): void
    {
        $this->withToken($this->donaturToken())
            ->getJson('/api/v1/admin/ping')->assertStatus(403);
    }

    public function test_token_staff_diblokir_dari_area_donatur(): void
    {
        $this->withToken($this->staffToken(UserRole::Admin))
            ->getJson('/api/v1/donatur/ping')->assertStatus(403);
    }

    public function test_tanpa_token_diblokir(): void
    {
        $this->getJson('/api/v1/admin/ping')->assertStatus(401);
    }

    private function staffToken(UserRole $role): string
    {
        return User::factory()->role($role)->create()
            ->createToken('test', ['staff'])->plainTextToken;
    }

    private function donaturToken(): string
    {
        return User::factory()->create()   // default role donatur
            ->createToken('test', ['donatur'])->plainTextToken;
    }
}
