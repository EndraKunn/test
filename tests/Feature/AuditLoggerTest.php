<?php

namespace Tests\Feature;

use App\Modules\Admin\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_data_is_removed_from_audit_payloads(): void
    {
        $entry = app(AuditLogger::class)->record(
            entity: 'security_test',
            entityId: 123,
            action: 'updated',
            before: [
                'display_name' => 'Warung Nusantara',
                'account_number' => '123456789012',
                'credentials' => [
                    'password' => 'password-rahasia',
                    'keep' => 'data-aman',
                ],
            ],
            after: [
                'status' => 'verified',
                'account_number_cipher' => 'encrypted-value',
                'two_factor_secret' => 'secret-2fa',
                'nested' => [
                    'refresh_token' => 'token-rahasia',
                    'safe' => 'data-aman',
                ],
            ],
            metadata: [
                'access_token' => 'token-rahasia',
                'description' => 'Audit pengujian',
            ],
        );

        $before = json_decode(
            $entry->getRawOriginal('before'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $after = json_decode(
            $entry->getRawOriginal('after'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $metadata = json_decode(
            $entry->getRawOriginal('metadata'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame([
            'display_name' => 'Warung Nusantara',
            'credentials' => [
                'keep' => 'data-aman',
            ],
        ], $before);

        $this->assertSame([
            'status' => 'verified',
            'nested' => [
                'safe' => 'data-aman',
            ],
        ], $after);

        $this->assertSame([
            'description' => 'Audit pengujian',
        ], $metadata);
    }
}
