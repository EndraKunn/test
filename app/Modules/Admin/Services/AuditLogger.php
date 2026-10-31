<?php

namespace App\Modules\Admin\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class AuditLogger
{
    /**
     * Record an audit event without storing sensitive attributes.
     *
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        string $entity,
        ?int $entityId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?int $tenantId = null,
        ?int $canteenId = null,
        ?array $metadata = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => Auth::id(),
            'canteen_id' => $canteenId,
            'tenant_id' => $tenantId,
            'entity' => $entity,
            'entity_id' => $entityId,
            'action' => $action,
            'request_id' => request()->header('X-Request-ID')
                ?? (string) Str::uuid(),
            'before' => $before !== null
                ? $this->sanitize($before)
                : null,
            'after' => $after !== null
                ? $this->sanitize($after)
                : null,
            'metadata' => $metadata !== null
                ? $this->sanitize($metadata)
                : null,
            'logged_at' => now(),
        ]);
    }

    /**
     * Remove sensitive attributes recursively before audit storage.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitize(array $data): array
    {
        $sensitiveKeys = [
            'account_number_cipher',
            'account_number',
            'password',
            'password_hash',
            'token',
            'access_token',
            'refresh_token',
            'api_token',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                unset($data[$key]);

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }
}
