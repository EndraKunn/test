<?php

namespace App\Support\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class LoginLockout
{
    public const MAX_FAILURES = 5;

    public const FAILURE_WINDOW_SECONDS = 600;

    public const LOCK_SECONDS = 900;

    public function recordFailure(string $email): bool
    {
        $now = now()->getTimestamp();
        $counterKey = $this->counterKey($email);
        $window = Cache::get($counterKey);

        if (
            $window === null
            || $now - $window['started_at'] >= self::FAILURE_WINDOW_SECONDS
        ) {
            $window = [
                'count' => 0,
                'started_at' => $now,
            ];
        }

        $window['count']++;

        if ($window['count'] < self::MAX_FAILURES) {
            Cache::put(
                $counterKey,
                $window,
                self::FAILURE_WINDOW_SECONDS,
            );

            return false;
        }

        Cache::put(
            $this->lockKey($email),
            $now + self::LOCK_SECONDS,
            self::LOCK_SECONDS,
        );

        Cache::forget($counterKey);

        return true;
    }

    public function isLocked(string $email): bool
    {
        $lockUntil = Cache::get($this->lockKey($email));

        if ($lockUntil === null) {
            return false;
        }

        if ($lockUntil <= now()->getTimestamp()) {
            Cache::forget($this->lockKey($email));

            return false;
        }

        return true;
    }

    public function minutesRemaining(string $email): int
    {
        $lockUntil = Cache::get($this->lockKey($email));

        if ($lockUntil === null) {
            return 0;
        }

        $remaining = $lockUntil - now()->getTimestamp();

        return $remaining > 0
            ? (int) ceil($remaining / 60)
            : 0;
    }

    public function clear(string $email): void
    {
        Cache::forget($this->counterKey($email));
        Cache::forget($this->lockKey($email));
    }

    private function counterKey(string $email): string
    {
        return 'auth:login:failures:'.$this->fingerprint($email);
    }

    private function lockKey(string $email): string
    {
        return 'auth:login:locked:'.$this->fingerprint($email);
    }

    private function fingerprint(string $email): string
    {
        return hash('sha256', Str::lower(trim($email)));
    }
}
