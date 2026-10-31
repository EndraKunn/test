<?php

namespace App\Support\Tokens;

use InvalidArgumentException;

/**
 * Token opaque untuk QR meja dan sesi pelanggan.
 *
 * Token mentah hanya diberikan saat penerbitan.
 * Database menyimpan hash SHA-256 biner, bukan token mentah.
 */
final class OpaqueToken
{
    /**
     * Generate token baru dan hash biner SHA-256.
     *
     * @return array{plain: string, hash: string}
     */
    public static function issue(int $bytes = 32): array
    {
        if ($bytes < 16) {
            throw new InvalidArgumentException(
                'Token minimal 128 bit (16 byte).',
            );
        }

        $plain = rtrim(
            strtr(base64_encode(random_bytes($bytes)), '+/', '-_'),
            '=',
        );

        return [
            'plain' => $plain,
            'hash' => hash('sha256', $plain, true),
        ];
    }

    /**
     * Calculate the binary SHA-256 hash for a plain token.
     */
    public static function hash(string $plain): string
    {
        return hash('sha256', $plain, true);
    }
}
