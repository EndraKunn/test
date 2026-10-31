<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\CustomerSession;
use App\Models\DiningTable;
use App\Modules\Admin\Services\QrTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrTableSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_qr_creates_anonymous_session_and_cookie(): void
    {
        $canteen = Canteen::factory()->create([
            'status' => 'active',
        ]);

        $table = DiningTable::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);

        $plainToken = app(QrTokenService::class)->issue($table);

        $response = $this->get(route('customer.scan', [
            'token' => $plainToken,
        ]));

        $response->assertRedirect(route('customer.home', [
            'canteen' => $canteen->slug,
        ]));

        $session = CustomerSession::query()->firstOrFail();

        $this->assertSame(
            $canteen->id,
            $session->canteen_id,
        );

        $this->assertSame(
            $table->id,
            $session->dining_table_id,
        );

        $this->assertSame('active', $session->status);

        $this->assertEqualsWithDelta(
            $session->created_at->copy()->addHours(4)->getTimestamp(),
            $session->expires_at->getTimestamp(),
            2,
        );
        $response->assertCookie('customer_session');

        $cookie = $response->getCookie('customer_session', false);

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function test_invalid_qr_returns_404_without_creating_session(): void
    {
        $this->get(route('customer.scan', [
            'token' => 'token-tidak-valid',
        ]))->assertNotFound();

        $this->assertDatabaseCount('customer_sessions', 0);
    }

    public function test_numeric_table_id_is_not_a_valid_qr_token(): void
    {
        $this->get(route('customer.scan', [
            'token' => '1',
        ]))->assertNotFound();

        $this->assertDatabaseCount('customer_sessions', 0);
    }

    public function test_revoked_qr_returns_404(): void
    {
        $canteen = Canteen::factory()->create([
            'status' => 'active',
        ]);

        $table = DiningTable::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);

        $service = app(QrTokenService::class);
        $oldToken = $service->issue($table);

        $service->rotate($table);

        $this->get(route('customer.scan', [
            'token' => $oldToken,
        ]))->assertNotFound();

        $this->assertDatabaseCount('customer_sessions', 0);
    }

    public function test_expired_qr_returns_404(): void
    {
        $canteen = Canteen::factory()->create([
            'status' => 'active',
        ]);

        $table = DiningTable::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);

        $expiredToken = app(QrTokenService::class)->issue(
            $table,
            now()->subMinute(),
        );

        $this->get(route('customer.scan', [
            'token' => $expiredToken,
        ]))->assertNotFound();

        $this->assertDatabaseCount('customer_sessions', 0);
    }

    public function test_inactive_canteen_rejects_qr(): void
    {
        $canteen = Canteen::factory()->create([
            'status' => 'inactive',
        ]);

        $table = DiningTable::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);

        $token = app(QrTokenService::class)->issue($table);

        $this->get(route('customer.scan', [
            'token' => $token,
        ]))->assertNotFound();

        $this->assertDatabaseCount('customer_sessions', 0);
    }

    public function test_inactive_table_rejects_existing_qr(): void
    {
        $canteen = Canteen::factory()->create([
            'status' => 'active',
        ]);

        $table = DiningTable::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);

        $token = app(QrTokenService::class)->issue($table);

        // Nonaktifkan meja tanpa menghapus token QR.
        $table->forceFill([
            'status' => 'inactive',
        ])->save();

        $this->get(route('customer.scan', [
            'token' => $token,
        ]))->assertNotFound();

        $this->assertDatabaseCount('customer_sessions', 0);
    }
}
