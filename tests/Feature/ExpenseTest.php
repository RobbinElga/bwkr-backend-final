<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DonationClaim;
use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_admin_buat_expense(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->post('/api/v1/admin/expenses', [
            'project_id'   => $project->id,
            'amount'       => 1_000_000,
            'receipt_file' => UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.needs_materai', false);

        $this->assertSame(0, $project->fresh()->amount_spent);  // masih pending
    }

    public function test_diatas_5jt_wajib_materai(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->post('/api/v1/admin/expenses', [
            'project_id'   => $project->id,
            'amount'       => 6_000_000,
            'receipt_file' => UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_diatas_5jt_dengan_materai_sukses(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->post('/api/v1/admin/expenses', [
            'project_id'   => $project->id,
            'amount'       => 6_000_000,
            'receipt_file' => UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf'),
            'materai_file' => UploadedFile::fake()->create('materai.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.needs_materai', true);
    }

    public function test_approve_menaikkan_amount_spent(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $expense = Expense::factory()->create(['project_id' => $project->id, 'amount' => 1_000_000, 'status' => 'pending']);

        $this->postJson("/api/v1/admin/expenses/{$expense->id}/approve")->assertOk();
        $this->assertSame(1_000_000, $project->fresh()->amount_spent);
    }

    public function test_admin_tidak_bisa_approve_diatas_5jt(): void
    {
        $this->actingAsAdmin();
        $expense = Expense::factory()->create(['amount' => 6_000_000, 'status' => 'pending', 'needs_materai' => true]);

        $this->postJson("/api/v1/admin/expenses/{$expense->id}/approve")->assertStatus(403);
    }

    public function test_super_admin_bisa_approve_diatas_5jt(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::SuperAdmin)->create(), ['staff']);
        $expense = Expense::factory()->create(['amount' => 6_000_000, 'status' => 'pending', 'needs_materai' => true]);

        $this->postJson("/api/v1/admin/expenses/{$expense->id}/approve")->assertOk();
    }

    public function test_remaining_funds_raised_minus_spent(): void
    {
        $project = Project::factory()->create(['target_amount' => 10_000_000]);
        DonationClaim::factory()->create(['project_id' => $project->id, 'amount' => 3_000_000, 'status' => 'approved']);
        Expense::factory()->create(['project_id' => $project->id, 'amount' => 1_000_000, 'status' => 'approved']);

        $p = $project->fresh();
        $this->assertSame(3_000_000, $p->amount_raised);
        $this->assertSame(1_000_000, $p->amount_spent);
        $this->assertSame(2_000_000, $p->remaining_funds);
    }

    public function test_cs_tidak_bisa_akses_expenses(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Cs)->create(), ['staff']);
        $this->getJson('/api/v1/admin/expenses')->assertStatus(403);
    }
}
