<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_admin_buat_berita_published_isi_published_at(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $this->post('/api/v1/admin/news', [
            'title'          => 'Kabar Wakaf',
            'content'        => '<p>isi berita</p>',
            'status'         => 'published',
            'tags'           => ['wakaf', 'pesantren'],
            'featured_image' => UploadedFile::fake()->image('n.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'kabar-wakaf')
            ->assertJsonPath('data.status', 'published');

        $this->assertNotNull(News::first()->published_at);
    }

    public function test_slug_berita_unik(): void
    {
        $this->actingAsAdmin();
        News::factory()->create(['title' => 'Kabar Wakaf', 'slug' => 'kabar-wakaf']);

        $this->postJson('/api/v1/admin/news', ['title' => 'Kabar Wakaf'])
            ->assertCreated()->assertJsonPath('data.slug', 'kabar-wakaf-2');
    }

    public function test_publik_hanya_lihat_published(): void
    {
        News::factory()->create(['status' => 'published']);
        News::factory()->create(['status' => 'draft']);

        $res = $this->getJson('/api/v1/news')->assertOk();
        $this->assertCount(1, $res->json('data'));
    }

    public function test_publik_404_berita_draft(): void
    {
        $n = News::factory()->create(['status' => 'draft']);
        $this->getJson("/api/v1/news/{$n->slug}")->assertNotFound();
    }

    public function test_admin_ubah_berita(): void
    {
        $this->actingAsAdmin();
        $n = News::factory()->create(['title' => 'Lama']);

        $this->putJson("/api/v1/admin/news/{$n->slug}", ['title' => 'Baru'])
            ->assertOk()->assertJsonPath('data.title', 'Baru');
    }

    public function test_admin_hapus_berita(): void
    {
        $this->actingAsAdmin();
        $n = News::factory()->create();

        $this->deleteJson("/api/v1/admin/news/{$n->slug}")->assertOk();
        $this->assertSoftDeleted('news', ['id' => $n->id]);
    }

    public function test_donatur_tidak_bisa_akses_admin_berita(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/news')->assertStatus(403);
    }
}
