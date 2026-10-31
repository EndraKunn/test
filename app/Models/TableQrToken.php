<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat token QR meja untuk pencatatan rotasi.
 *
 * Token disimpan dalam bentuk hash SHA-256 biner.
 * token_hash dan dining_table_id hanya boleh diatur
 * oleh service tepercaya.
 *
 * @property int $dining_table_id
 * @property string $status
 * @property CarbonImmutable|null $issued_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $revoked_at
 */
class TableQrToken extends Model
{
    protected $fillable = ['status'];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DiningTable, $this>
     */
    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->revoked_at === null
            && (
                $this->expires_at === null
                || $this->expires_at->isFuture()
            );
    }

    /**
     * @param  Builder<TableQrToken>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query
            ->where('status', 'active')
            ->whereNull('revoked_at');
    }
}
