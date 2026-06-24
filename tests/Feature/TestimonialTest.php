<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TestimonialTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_admin_buat_testimoni(): void
    {
        $this->actingAsAdmin();
        $this->postJson('/api/v1/admin/testimonials', [
            'name' => 'Ust. Ahmad',
            'content' => 'Lembaga amanah.',
        ])->assertCreated()->assertJsonPath('data.name', 'Ust. Ahmad');
    }

    public function test_publik_hanya_visible_terurut(): void
    {
        Testimonial::factory()->create(['name' => 'B', 'order' => 2, 'is_visible' => true]);
        Testimonial::factory()->create(['name' => 'A', 'order' => 1, 'is_visible' => true]);
        Testimonial::factory()->create(['name' => 'C', 'is_visible' => false]);

        $res = $this->getJson('/api/v1/testimonials')->assertOk();
        $this->assertCount(2, $res->json('data'));
        $this->assertSame('A', $res->json('data.0.name'));   // order 1 dulu
    }

    public function test_admin_hapus_testimoni(): void
    {
        $this->actingAsAdmin();
        $t = Testimonial::factory()->create();
        $this->deleteJson("/api/v1/admin/testimonials/{$t->id}")->assertOk();
        $this->assertSoftDeleted('testimonials', ['id' => $t->id]);
    }

    public function test_donatur_diblokir(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/testimonials')->assertStatus(403);
    }
}
