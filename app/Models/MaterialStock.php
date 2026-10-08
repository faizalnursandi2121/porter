<?php

namespace App\Models;

use Database\Factories\MaterialStockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FR-29/30: consumable balance per school per material; balance >= 0 enforced by the
 * material_stocks_balance_non_negative CHECK; movements mutate it transactionally.
 *
 * @property int $site_id
 * @property string $name
 * @property string $unit
 * @property int $balance
 */
class MaterialStock extends Model
{
    /** @use HasFactory<MaterialStockFactory> */
    use HasFactory;

    protected $fillable = ['site_id', 'name', 'unit', 'balance'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
