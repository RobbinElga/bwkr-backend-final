<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProgramTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_admin_bisa_membuat_program_dengan_gambar(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $this->postJson('/api/v1/admin/programs', [
            'name'        => 'Wakaf Sumur',
            'description' => 'Program sumur air bersih',
            'image'       => UploadedFile::fake()->image('sumur.jpg'),
        ])->assertCreated()->assertJsonPath('data.slug', 'wakaf-sumur');

        $this->assertStringEndsWith('.webp', Program::first()->image);
    }

    public function test_slug_dibuat_unik_otomatis(): void
    {
        $this->actingAsAdmin();
        Program::factory()->create(['name' => 'Wakaf Sumur', 'slug' => 'wakaf-sumur']);

        $this->postJson('/api/v1/admin/programs', ['name' => 'Wakaf Sumur'])
            ->assertCreated()->assertJsonPath('data.slug', 'wakaf-sumur-2');
    }

    public function test_admin_bisa_mengubah_program(): void
    {
        $this->actingAsAdmin();
        $program = Program::factory()->create(['name' => 'Lama']);

        $this->putJson("/api/v1/admin/programs/{$program->slug}", ['name' => 'Baru'])
            ->assertOk()->assertJsonPath('data.name', 'Baru');
    }

    public function test_admin_bisa_menghapus_program(): void
    {
        $this->actingAsAdmin();
        $program = Program::factory()->create();

        $this->deleteJson("/api/v1/admin/programs/{$program->slug}")->assertOk();
        $this->assertSoftDeleted('programs', ['id' => $program->id]);
    }

    public function test_audit_log_tercatat_saat_membuat(): void
    {
        $this->actingAsAdmin();
        $this->postJson('/api/v1/admin/programs', ['name' => 'Wakaf Quran']);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'created',
            'model_type' => Program::class,
        ]);
    }

    public function test_publik_hanya_lihat_program_aktif(): void
    {
        Program::factory()->create(['status' => 'aktif']);
        Program::factory()->create(['status' => 'nonaktif']);

        $res = $this->getJson('/api/v1/programs')->assertOk();
        $this->assertCount(1, $res->json('data'));
    }

    public function test_publik_404_program_nonaktif(): void
    {
        $p = Program::factory()->create(['status' => 'nonaktif']);
        $this->getJson("/api/v1/programs/{$p->slug}")->assertNotFound();
    }

    public function test_donatur_tidak_bisa_akses_admin_program(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/programs')->assertStatus(403);
    }
}
