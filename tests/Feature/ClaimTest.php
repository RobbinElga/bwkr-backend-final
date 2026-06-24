<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendWhatsAppNotification;
use App\Models\DonationClaim;
use App\Models\DonationInput;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClaimTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
    }

    public function test_admin_bisa_membuat_claim(): void
    {
        $this->actingAsAdmin();
        $input   = DonationInput::factory()->create(['amount' => 1_000_000]);
        $project = Project::factory()->create();

        $this->postJson('/api/v1/admin/donations-claim', [
            'donation_input_id' => $input->id,
            'project_id'        => $project->id,
            'amount'            => 400000,
        ])->assertCreated()->assertJsonPath('data.amount', 400000);
    }

    public function test_claim_melebihi_sisa_ditolak(): void
    {
        $this->actingAsAdmin();
        $input   = DonationInput::factory()->create(['amount' => 500000]);
        $project = Project::factory()->create();

        $this->postJson('/api/v1/admin/donations-claim', [
            'donation_input_id' => $input->id,
            'project_id'        => $project->id,
            'amount'            => 600000,
        ])->assertStatus(422);
    }

    public function test_approve_menaikkan_amount_raised(): void
    {
        Queue::fake();
        $this->actingAsAdmin();
        $input   = DonationInput::factory()->create(['amount' => 1_000_000]);
        $project = Project::factory()->create(['target_amount' => 5_000_000]);
        $claim   = DonationClaim::factory()->create([
            'donation_input_id' => $input->id,
            'project_id'        => $project->id,
            'amount'            => 1_000_000,
            'status'            => 'pending',
        ]);

        $this->postJson("/api/v1/admin/donations-claim/{$claim->id}/approve")->assertOk();

        $this->assertSame(1_000_000, $project->fresh()->amount_raised);
        $this->assertSame('claimed', $input->fresh()->status->value);
        Queue::assertPushed(SendWhatsAppNotification::class);
    }

    public function test_claim_pending_tidak_dihitung(): void
    {
        $project = Project::factory()->create();
        DonationClaim::factory()->create([
            'project_id' => $project->id,
            'amount' => 500000,
            'status' => 'pending',
        ]);

        $this->assertSame(0, $project->fresh()->amount_raised);
    }

    public function test_split_ke_dua_proyek(): void
    {
        Queue::fake();
        $this->actingAsAdmin();
        $input = DonationInput::factory()->create(['amount' => 1_000_000]);
        $p1 = Project::factory()->create();
        $p2 = Project::factory()->create();

        $c1 = DonationClaim::factory()->create(['donation_input_id' => $input->id, 'project_id' => $p1->id, 'amount' => 600000, 'status' => 'pending']);
        $c2 = DonationClaim::factory()->create(['donation_input_id' => $input->id, 'project_id' => $p2->id, 'amount' => 400000, 'status' => 'pending']);

        $this->postJson("/api/v1/admin/donations-claim/{$c1->id}/approve")->assertOk();
        $this->postJson("/api/v1/admin/donations-claim/{$c2->id}/approve")->assertOk();

        $this->assertSame(600000, $p1->fresh()->amount_raised);
        $this->assertSame(400000, $p2->fresh()->amount_raised);
        $this->assertSame('claimed', $input->fresh()->status->value);   // teralokasi penuh
    }

    public function test_approve_ulang_ditolak(): void
    {
        Queue::fake();
        $this->actingAsAdmin();
        $claim = DonationClaim::factory()->create(['status' => 'approved']);

        $this->postJson("/api/v1/admin/donations-claim/{$claim->id}/approve")->assertStatus(422);
    }

    public function test_admin_bisa_reject(): void
    {
        $this->actingAsAdmin();
        $claim = DonationClaim::factory()->create(['status' => 'pending']);

        $this->postJson("/api/v1/admin/donations-claim/{$claim->id}/reject", ['notes' => 'Bukti tidak valid'])
            ->assertOk()->assertJsonPath('data.status', 'rejected');
    }

    public function test_cs_tidak_bisa_akses_claim(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Cs)->create(), ['staff']);
        $this->getJson('/api/v1/admin/donations-claim')->assertStatus(403);
    }

    public function test_donatur_tidak_bisa_akses_claim(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['donatur']);
        $this->getJson('/api/v1/admin/donations-claim')->assertStatus(403);
    }
}
