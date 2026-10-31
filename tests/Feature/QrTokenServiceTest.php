<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\DiningTable;
use App\Models\TableQrToken;
use App\Modules\Admin\Services\QrTokenService;
use App\Support\Tokens\OpaqueToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_stores_hash_instead_of_plain_token(): void
    {
        $canteen = Canteen::factory()->create();

        $table = DiningTable::factory()->create([
            'canteen_id' => $canteen->id,
        ]);

        $plainToken = app(QrTokenService::class)->issue($table);

        $qr = TableQrToken::query()
            ->where('dining_table_id', $table->id)
            ->firstOrFail();

        $this->assertSame(
            OpaqueToken::hash($plainToken),
            $qr->token_hash,
        );

        $this->assertSame('active', $qr->status);
        $this->assertNotSame($plainToken, $qr->token_hash);
    }

    public function test_rotation_revokes_old_token_and_keeps_one_active(): void
    {
        $canteen = Canteen::factory()->create();

        $table = DiningTable::factory()->create([
            'canteen_id' => $canteen->id,
        ]);

        $service = app(QrTokenService::class);

        $firstToken = $service->issue($table);

        $oldQr = TableQrToken::query()
            ->where('dining_table_id', $table->id)
            ->firstOrFail();

        $secondToken = $service->rotate($table);

        $oldQr->refresh();

        $this->assertNotSame($firstToken, $secondToken);
        $this->assertSame('revoked', $oldQr->status);
        $this->assertNotNull($oldQr->revoked_at);

        $this->assertSame(
            1,
            TableQrToken::query()
                ->where('dining_table_id', $table->id)
                ->where('status', 'active')
                ->whereNull('revoked_at')
                ->count(),
        );

        $newQr = TableQrToken::query()
            ->where('dining_table_id', $table->id)
            ->where('status', 'active')
            ->firstOrFail();

        $this->assertSame(
            OpaqueToken::hash($secondToken),
            $newQr->token_hash,
        );
    }
}
