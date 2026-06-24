<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Program;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_admin_bisa_membuat_project_dengan_gambar(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $program = Program::factory()->create();

        $this->post('/api/v1/admin/projects', [
            'program_id'    => $program->id,
            'name'          => 'Pembangunan Masjid',
            'target_amount' => 50_000_000,
            'status'        => 'berjalan',
            'images'        => [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.png'),
            ],
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'pembangunan-masjid')
            ->assertJsonPath('data.target_amount', 50000000);

        $this->assertCount(2, Project::first()->images);
    }

    public function test_slug_project_unik(): void
    {
        $this->actingAsAdmin();
        $program = Program::factory()->create();
        Project::factory()->create(['name' => 'Masjid', 'slug' => 'masjid', 'program_id' => $program->id]);

        $this->postJson('/api/v1/admin/projects', ['program_id' => $program->id, 'name' => 'Masjid'])
            ->assertCreated()->assertJsonPath('data.slug', 'masjid-2');
    }

    public function test_field_terhitung_muncul(): void
    {
        $project = Project::factory()->create(['target_amount' => 10_000_000, 'status' => 'berjalan']);

        $this->getJson("/api/v1/projects/{$project->slug}")->assertOk()
            ->assertJsonPath('data.amount_raised', 0)       // stub Sprint 3
            ->assertJsonPath('data.amount_spent', 0)        // stub Sprint 4
            ->assertJsonPath('data.shortfall', 10000000)
            ->assertJsonPath('data.remaining_funds', 0);
    }

    public function test_admin_bisa_mengubah_project(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['name' => 'Lama']);

        $this->putJson("/api/v1/admin/projects/{$project->slug}", ['name' => 'Baru'])
            ->assertOk()->assertJsonPath('data.name', 'Baru');
    }

    public function test_admin_bisa_menghapus_project(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->deleteJson("/api/v1/admin/projects/{$project->slug}")->assertOk();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_publik_sembunyikan_draft(): void
    {
        Project::factory()->create(['status' => 'berjalan']);
        Project::factory()->create(['status' => 'selesai']);
        Project::factory()->create(['status' => 'draft']);

        $res = $this->getJson('/api/v1/projects')->assertOk();
        $this->assertCount(2, $res->json('data'));   // draft tidak tampil
    }

    public function test_publik_404_project_draft(): void
    {
        $p = Project::factory()->create(['status' => 'draft']);
        $this->getJson("/api/v1/projects/{$p->slug}")->assertNotFound();
    }

    public function test_donatur_tidak_bisa_akses_admin_project(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/projects')->assertStatus(403);
    }
}
