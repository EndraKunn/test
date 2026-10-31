<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Sesi pelanggan anonim yang terikat pada kantin dan meja.
 *
 * Hash token sesi dan foreign key diatur oleh service tepercaya.
 * Cookie menyimpan token mentah, sedangkan database menyimpan hash.
 *
 * @property int $canteen_id
 * @property int|null $dining_table_id
 * @property string $status
 * @property Carbon|null $expires_at
 */
class CustomerSession extends Model
{
    use HasUlids;

    protected $fillable = ['status'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Canteen, $this> */
    public function canteen(): BelongsTo
    {
        return $this->belongsTo(Canteen::class);
    }

    /** @return BelongsTo<DiningTable, $this> */
    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && (
                $this->expires_at === null
                || $this->expires_at->isFuture()
            );
    }
}
