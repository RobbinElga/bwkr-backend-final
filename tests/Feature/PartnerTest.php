<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PartnerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_admin_buat_mitra(): void
    {
        $this->actingAsAdmin();
        $this->postJson('/api/v1/admin/partners', ['name' => 'PT Berkah'])
            ->assertCreated()->assertJsonPath('data.name', 'PT Berkah');
    }

    public function test_publik_hanya_visible_dan_tanpa_data_pic(): void
    {
        Partner::factory()->create(['is_visible' => true, 'pic_phone' => '0812rahasia']);
        Partner::factory()->create(['is_visible' => false]);

        $res = $this->getJson('/api/v1/partners')->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertArrayNotHasKey('pic_phone', $res->json('data.0'));  // PIC tidak bocor
    }

    public function test_admin_hapus_mitra(): void
    {
        $this->actingAsAdmin();
        $p = Partner::factory()->create();
        $this->deleteJson("/api/v1/admin/partners/{$p->id}")->assertOk();
        $this->assertSoftDeleted('partners', ['id' => $p->id]);
    }

    public function test_donatur_diblokir(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/partners')->assertStatus(403);
    }
}
