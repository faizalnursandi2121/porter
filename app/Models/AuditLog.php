<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * FR-46/47: append-only audit row; no updated_at, never updated or deleted by the app.
 * occurred_at is UTC; occurred_date_local carries the actor's site-local date for filtering.
 *
 * @property int|null $actor_id
 * @property string $actor_role
 * @property string $action
 * @property string $object_type
 * @property int|null $object_id
 * @property Carbon $occurred_at
 * @property Carbon $occurred_date_local
 * @property array|null $before
 * @property array|null $after
 */
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'actor_id', 'actor_role', 'action', 'object_type', 'object_id',
        'occurred_at', 'occurred_date_local', 'before', 'after',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'occurred_date_local' => 'date',
            'before' => 'array',
            'after' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
