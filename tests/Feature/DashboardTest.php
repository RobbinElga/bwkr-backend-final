<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BankAccount;
use App\Models\DonationClaim;
use App\Models\DonationInput;
use App\Models\Expense;
use App\Models\Program;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_menampilkan_ringkasan(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);

        $program = Program::factory()->create(['status' => 'aktif']);
        Program::factory()->create(['status' => 'nonaktif']);

        $project  = Project::factory()->create(['program_id' => $program->id, 'status' => 'berjalan']);
        Project::factory()->create(['program_id' => $program->id, 'status' => 'selesai']);

        $bank  = BankAccount::factory()->create(['is_active' => true]);
        $input = DonationInput::factory()->create(['bank_account_id' => $bank->id, 'amount' => 2_000_000]);

        DonationClaim::factory()->create([
            'donation_input_id' => $input->id,
            'project_id'        => $project->id,
            'amount'            => 2_000_000,
            'status'            => 'approved',
        ]);
        Expense::factory()->create([
            'project_id'      => $project->id,
            'bank_account_id' => $bank->id,
            'amount'          => 500_000,
            'status'          => 'approved',
        ]);

        $res = $this->getJson('/api/v1/admin/dashboard')->assertOk();

        $res->assertJsonPath('programs.active', 1)
            ->assertJsonPath('projects.total', 2)
            ->assertJsonPath('projects.running', 1)
            ->assertJsonPath('projects.completed', 1)
            ->assertJsonPath('funds.total_raised', 2000000)
            ->assertJsonPath('funds.total_disbursed', 500000)
            ->assertJsonPath('funds.remaining', 1500000)
            ->assertJsonPath('donors.total', 1)
            ->assertJsonPath('staff_active', 1);

        // saldo rekening = masuk 2jt - keluar 500rb = 1,5jt
        $this->assertSame(1_500_000, $res->json('balance_per_account.0.balance'));
    }

    public function test_cs_tidak_bisa_akses_dashboard(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Cs)->create(), ['staff']);
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }

    public function test_donatur_tidak_bisa_akses_dashboard(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }
}
