<?php

namespace Tests\Feature;

use App\Models\CommissionScheme;
use App\Models\Tenant;
use App\Modules\Admin\Services\ChangeCommissionSchedule;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommissionScheduleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_commission_ends_exactly_when_new_commission_starts(): void
    {
        $tenant = Tenant::factory()->create();

        $oldStart = Carbon::parse('2026-01-01 00:00:00');
        $effectiveAt = Carbon::parse('2026-07-01 00:00:00');

        $old = CommissionScheme::create([
            'tenant_id' => $tenant->id,
            'commission_rate' => '0.1000',
            'valid_from' => $oldStart,
            'valid_to' => null,
        ]);

        $new = app(ChangeCommissionSchedule::class)->handle(
            $tenant,
            '0.1200',
            $effectiveAt,
        );

        $this->assertEquals(
            $effectiveAt->timestamp,
            $old->fresh()->valid_to->timestamp,
        );

        $this->assertSame('0.1200', $new->commission_rate);
        $this->assertEquals($effectiveAt->timestamp, $new->valid_from->timestamp);
        $this->assertNull($new->valid_to);

        $beforeBoundary = CommissionScheme::query()
            ->effectiveAt(Carbon::parse('2026-06-30 23:59:59.999999'))
            ->firstOrFail();

        $atBoundary = CommissionScheme::query()
            ->effectiveAt($effectiveAt)
            ->firstOrFail();

        $this->assertSame($old->id, $beforeBoundary->id);
        $this->assertSame($new->id, $atBoundary->id);

        $this->assertSame(
            1,
            DB::table('commission_schemes')
                ->where('tenant_id', $tenant->id)
                ->whereNull('valid_to')
                ->count(),
        );
    }

    public function test_it_rejects_an_effective_time_before_active_version_start(): void
    {
        $tenant = Tenant::factory()->create();

        $active = CommissionScheme::create([
            'tenant_id' => $tenant->id,
            'commission_rate' => '0.1000',
            'valid_from' => Carbon::parse('2026-06-15 00:00:00'),
            'valid_to' => null,
        ]);

        try {
            app(ChangeCommissionSchedule::class)->handle(
                $tenant,
                '0.1200',
                Carbon::parse('2026-06-14 00:00:00'),
            );

            $this->fail('Waktu efektif yang lebih awal seharusnya ditolak.');
        } catch (DomainException $exception) {
            $this->assertSame(
                'Waktu efektif harus setelah versi aktif (mencegah overlap).',
                $exception->getMessage(),
            );
        }

        $this->assertNull($active->fresh()->valid_to);
    }

    public function test_database_rejects_two_active_commission_schemes_for_one_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        CommissionScheme::create([
            'tenant_id' => $tenant->id,
            'commission_rate' => '0.1000',
            'valid_from' => Carbon::parse('2026-01-01 00:00:00'),
            'valid_to' => null,
        ]);

        $this->expectException(QueryException::class);

        CommissionScheme::create([
            'tenant_id' => $tenant->id,
            'commission_rate' => '0.1200',
            'valid_from' => Carbon::parse('2026-07-01 00:00:00'),
            'valid_to' => null,
        ]);
    }
}
