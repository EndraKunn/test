<?php

namespace App\Modules\Ordering\Services;

use App\Models\CustomerSession;
use App\Support\Tokens\OpaqueToken;
use Illuminate\Http\Request;

/**
 * Membaca sesi pelanggan aktif dari token mentah atau cookie HttpOnly `customer_session`.
 */
class ResolveCustomerSession
{
    public const COOKIE_NAME = 'customer_session';

    public function current(Request $request): ?CustomerSession
    {
        $plain = $request->cookie(self::COOKIE_NAME);

        if (! is_string($plain) || $plain === '') {
            return null;
        }

        return $this->fromToken($plain);
    }

    public function fromToken(?string $plainToken): ?CustomerSession
    {
        if (! is_string($plainToken) || $plainToken === '') {
            return null;
        }

        $session = CustomerSession::query()
            ->with('diningTable')
            ->where('session_token_hash', OpaqueToken::hash($plainToken))
            ->first();

        if ($session === null || ! $session->isActive()) {
            return null;
        }

        return $session;
    }
}
