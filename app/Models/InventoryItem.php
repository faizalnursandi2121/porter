<?php

namespace App\Models;

use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * FR-27/28/31: school-bound asset record; status values are the locked Indonesian terms
 * per FR-28 — rusak/hilang require status_reason (app-level rule, enforced by FormRequest
 * in the inventory stories).
 *
 * @property int $site_id
 * @property string $name
 * @property string $category
 * @property int $quantity
 * @property Carbon $received_at
 * @property string $status
 * @property string|null $status_reason
 */
class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory;

    public const DIPAKAI = 'dipakai';

    public const CADANGAN = 'cadangan';

    public const RUSAK = 'rusak';

    public const DIKEMBALIKAN = 'dikembalikan';

    public const HILANG = 'hilang';

    public const STATUSES = [
        self::DIPAKAI, self::CADANGAN, self::RUSAK, self::DIKEMBALIKAN, self::HILANG,
    ];

    protected $fillable = [
        'site_id', 'name', 'category', 'quantity', 'received_at', 'status', 'status_reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(InventoryPhoto::class);
    }
}
