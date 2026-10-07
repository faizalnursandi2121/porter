<?php

namespace App\Models;

use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * FR-3: placement history; one active assignment per EOS and per site enforced by
 * partial unique indexes (assignments_one_active_per_eos / assignments_one_active_per_site).
 *
 * @property int $user_id
 * @property int $site_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 */
class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'site_id', 'started_at', 'ended_at'];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
