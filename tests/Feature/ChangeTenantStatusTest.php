<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Admin\Services\ChangeTenantStatus;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChangeTenantStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deactivates_tenant_and_records_audit(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $updated = app(ChangeTenantStatus::class)->handle(
            $tenant,
            'inactive',
        );

        $this->assertSame('inactive', $updated->status);
        $this->assertSame(
            'inactive',
            $tenant->fresh()->status,
        );

        $audit = DB::table('audit_logs')
            ->where('entity', 'tenant')
            ->where('entity_id', (string) $tenant->id)
            ->where('action', 'deactivated')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame(
            ['status' => 'active'],
            json_decode($audit->before, true),
        );
        $this->assertSame(
            ['status' => 'inactive'],
            json_decode($audit->after, true),
        );
    }

    public function test_it_activates_inactive_tenant_and_records_audit(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'inactive',
        ]);

        $updated = app(ChangeTenantStatus::class)->handle(
            $tenant,
            'active',
        );

        $this->assertSame('active', $updated->status);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'tenant',
            'entity_id' => (string) $tenant->id,
            'action' => 'activated',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_unchanged_status_does_not_create_duplicate_audit(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $beforeCount = DB::table('audit_logs')
            ->where('entity', 'tenant')
            ->where('entity_id', (string) $tenant->id)
            ->count();

        app(ChangeTenantStatus::class)->handle(
            $tenant,
            'active',
        );

        $afterCount = DB::table('audit_logs')
            ->where('entity', 'tenant')
            ->where('entity_id', (string) $tenant->id)
            ->count();

        $this->assertSame($beforeCount, $afterCount);
    }

    public function test_it_rejects_invalid_tenant_status(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Status tenant tidak dikenal.');

        app(ChangeTenantStatus::class)->handle(
            $tenant,
            'suspended',
        );
    }
}
