<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_admin_bisa_membuat_laporan_dengan_cover_dan_pdf(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $this->postJson('/api/v1/admin/reports', [
            'title'    => 'Laporan Tahunan 2025',
            'category' => 'tahunan',
            'year'     => 2025,
            'cover'    => UploadedFile::fake()->image('cover.jpg'),
            'file'     => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('data.slug', 'laporan-tahunan-2025');

        $report = Report::first();
        $this->assertStringEndsWith('.webp', $report->cover);
        $this->assertNotNull($report->file_path);
    }

    public function test_slug_laporan_unik(): void
    {
        $this->actingAsAdmin();
        Report::factory()->create(['title' => 'Laporan X', 'slug' => 'laporan-x']);

        $this->postJson('/api/v1/admin/reports', ['title' => 'Laporan X', 'category' => 'tahunan'])
            ->assertCreated()->assertJsonPath('data.slug', 'laporan-x-2');
    }

    public function test_publik_hanya_lihat_laporan_terbit(): void
    {
        Report::factory()->create(['is_published' => true]);
        Report::factory()->create(['is_published' => false]);

        $res = $this->getJson('/api/v1/reports')->assertOk();
        $this->assertCount(1, $res->json('data'));
    }

    public function test_publik_bisa_filter_kategori(): void
    {
        Report::factory()->create(['category' => 'tahunan']);
        Report::factory()->create(['category' => 'keuangan']);

        $res = $this->getJson('/api/v1/reports?category=keuangan')->assertOk();
        $this->assertCount(1, $res->json('data'));
    }

    public function test_publik_404_laporan_belum_terbit(): void
    {
        $r = Report::factory()->create(['is_published' => false]);
        $this->getJson("/api/v1/reports/{$r->slug}")->assertNotFound();
    }

    public function test_donatur_tidak_bisa_akses_admin_laporan(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/reports')->assertStatus(403);
    }
}
