<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_admin_buat_pencapaian(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/admin/achievements', [
            'count' => 1500,
            'label' => 'Penerima Manfaat',
            'period' => '2025',
        ])->assertCreated()->assertJsonPath('data.count', 1500);
    }

    public function test_publik_lihat_pencapaian_terurut(): void
    {
        Achievement::factory()->create(['label' => 'B', 'order' => 2]);
        Achievement::factory()->create(['label' => 'A', 'order' => 1]);

        $res = $this->getJson('/api/v1/achievements')->assertOk();
        $this->assertSame('A', $res->json('data.0.label'));
    }

    public function test_admin_hapus_pencapaian(): void
    {
        $this->actingAsAdmin();
        $a = Achievement::factory()->create();
        $this->deleteJson("/api/v1/admin/achievements/{$a->id}")->assertOk();
        $this->assertDatabaseMissing('achievements', ['id' => $a->id]);
    }

    public function test_donatur_diblokir(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/achievements')->assertStatus(403);
    }
}
