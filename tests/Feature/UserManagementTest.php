<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuper(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::SuperAdmin)->create(), ['staff']);
    }

    public function test_super_admin_buat_staff(): void
    {
        $this->actingAsSuper();

        $this->postJson('/api/v1/admin/users', [
            'name'                  => 'CS Baru',
            'email'                 => 'cs@bwkr.id',
            'phone'                 => '081200000003',
            'role'                  => 'cs',
            'password'              => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
        ])->assertCreated()->assertJsonPath('data.role', 'cs');
    }

    public function test_tidak_bisa_buat_donatur_lewat_endpoint_ini(): void
    {
        $this->actingAsSuper();

        $this->postJson('/api/v1/admin/users', [
            'name'                  => 'X',
            'email'                 => 'x@bwkr.id',
            'phone'                 => '0812',
            'role'                  => 'donatur',
            'password'              => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
        ])->assertStatus(422);
    }

    public function test_admin_biasa_tidak_bisa_akses_users(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
        $this->getJson('/api/v1/admin/users')->assertStatus(403);
    }

    public function test_reset_password(): void
    {
        $this->actingAsSuper();
        $staff = User::factory()->role(UserRole::Cs)->create();
        $staff->createToken('x', ['staff']);

        $res = $this->postJson("/api/v1/admin/users/{$staff->id}/reset-password")->assertOk();
        $this->assertNotEmpty($res->json('temporary_password'));
        $this->assertCount(0, $staff->fresh()->tokens);
    }

    public function test_reset_2fa(): void
    {
        $this->actingAsSuper();
        $staff = User::factory()->role(UserRole::Cs)->create([
            'two_factor_enabled' => true,
            'two_factor_secret'  => 'SECRET',
        ]);

        $this->postJson("/api/v1/admin/users/{$staff->id}/reset-2fa")->assertOk();
        $this->assertFalse($staff->fresh()->two_factor_enabled);
    }

    public function test_tidak_bisa_hapus_diri_sendiri(): void
    {
        $super = User::factory()->role(UserRole::SuperAdmin)->create();
        Sanctum::actingAs($super, ['staff']);

        $this->deleteJson("/api/v1/admin/users/{$super->id}")->assertStatus(422);
    }

    public function test_super_bisa_hapus_staff_lain(): void
    {
        $this->actingAsSuper();
        $staff = User::factory()->role(UserRole::Cs)->create();

        $this->deleteJson("/api/v1/admin/users/{$staff->id}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $staff->id]);
    }
}
