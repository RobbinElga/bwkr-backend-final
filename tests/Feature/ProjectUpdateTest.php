<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_admin_bisa_membuat_update(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->postJson("/api/v1/admin/projects/{$project->slug}/updates", [
            'title'   => 'Progress minggu 1',
            'content' => '<p>Pondasi selesai</p>',
        ])->assertCreated()->assertJsonPath('data.title', 'Progress minggu 1');

        $this->assertDatabaseHas('project_updates', [
            'project_id' => $project->id,
            'title'      => 'Progress minggu 1',
        ]);
    }

    public function test_update_muncul_di_detail_proyek_publik(): void
    {
        $project = Project::factory()->create(['status' => 'berjalan']);
        ProjectUpdate::factory()->for($project)->create(['title' => 'Kabar terbaru']);

        $res = $this->getJson("/api/v1/projects/{$project->slug}")->assertOk();
        $this->assertCount(1, $res->json('data.updates'));
        $this->assertSame('Kabar terbaru', $res->json('data.updates.0.title'));
    }

    public function test_update_terurut_terbaru_dulu(): void
    {
        $project = Project::factory()->create(['status' => 'berjalan']);
        ProjectUpdate::factory()->for($project)->create(['title' => 'Lama', 'published_at' => now()->subDays(5)]);
        ProjectUpdate::factory()->for($project)->create(['title' => 'Baru', 'published_at' => now()]);

        $res = $this->getJson("/api/v1/projects/{$project->slug}")->assertOk();
        $this->assertSame('Baru', $res->json('data.updates.0.title'));
    }

    public function test_admin_bisa_menghapus_update(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $update  = ProjectUpdate::factory()->for($project)->create();

        $this->deleteJson("/api/v1/admin/projects/{$project->slug}/updates/{$update->id}")->assertOk();
        $this->assertDatabaseMissing('project_updates', ['id' => $update->id]);
    }

    public function test_update_scoped_ke_proyeknya(): void
    {
        $this->actingAsAdmin();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        $update   = ProjectUpdate::factory()->for($projectB)->create();

        // update milik B diakses lewat URL A -> 404
        $this->getJson("/api/v1/admin/projects/{$projectA->slug}/updates/{$update->id}")
            ->assertNotFound();
    }

    public function test_donatur_tidak_bisa_akses_admin_update(): void
    {
        $project = Project::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['donatur']);

        $this->getJson("/api/v1/admin/projects/{$project->slug}/updates")->assertStatus(403);
    }
}
