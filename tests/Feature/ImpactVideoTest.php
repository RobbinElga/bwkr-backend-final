<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ImpactVideo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImpactVideoTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_admin_buat_video_dan_youtube_id_terbaca(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/admin/impact-videos', [
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'caption'     => 'Penyaluran',
        ])->assertCreated()->assertJsonPath('data.youtube_id', 'dQw4w9WgXcQ');
    }

    public function test_publik_lihat_video_terurut(): void
    {
        ImpactVideo::factory()->create(['caption' => 'B', 'order' => 2]);
        ImpactVideo::factory()->create(['caption' => 'A', 'order' => 1]);

        $res = $this->getJson('/api/v1/impact-videos')->assertOk();
        $this->assertSame('A', $res->json('data.0.caption'));
    }

    public function test_admin_hapus_video(): void
    {
        $this->actingAsAdmin();
        $v = ImpactVideo::factory()->create();
        $this->deleteJson("/api/v1/admin/impact-videos/{$v->id}")->assertOk();
        $this->assertDatabaseMissing('impact_videos', ['id' => $v->id]);
    }

    public function test_donatur_diblokir(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/impact-videos')->assertStatus(403);
    }
}
