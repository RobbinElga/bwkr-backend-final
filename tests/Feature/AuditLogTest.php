<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_bisa_lihat_audit_logs(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::SuperAdmin)->create(), ['staff']);
        AuditLog::create(['action' => 'created', 'model_type' => 'App\Models\Program', 'model_id' => 1]);

        $res = $this->getJson('/api/v1/admin/audit-logs')->assertOk();
        $this->assertGreaterThanOrEqual(1, count($res->json('data')));
    }

    public function test_admin_biasa_tidak_bisa_lihat_audit_logs(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create(), ['staff']);
        $this->getJson('/api/v1/admin/audit-logs')->assertStatus(403);
    }
}
