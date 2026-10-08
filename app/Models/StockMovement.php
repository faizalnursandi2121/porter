<?php

namespace App\Models;

use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FR-29: immutable ledger row; before/after balances written at creation; types cover
 * receipt, usage, correction, damage, loss, and return flows.
 *
 * @property int $material_stock_id
 * @property int $user_id
 * @property string $type
 * @property int $quantity
 * @property int $before_balance
 * @property int $after_balance
 * @property string|null $reason
 */
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    public const RECEIPT = 'RECEIPT';

    public const USAGE = 'USAGE';

    public const ADJUSTMENT = 'ADJUSTMENT';

    public const DAMAGED = 'DAMAGED';

    public const LOST = 'LOST';

    public const RETURN = 'RETURN';

    public const TYPES = [
        self::RECEIPT, self::USAGE, self::ADJUSTMENT, self::DAMAGED, self::LOST, self::RETURN,
    ];

    protected $fillable = [
        'material_stock_id', 'user_id', 'type', 'quantity', 'before_balance', 'after_balance', 'reason',
    ];

    public function materialStock(): BelongsTo
    {
        return $this->belongsTo(MaterialStock::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
