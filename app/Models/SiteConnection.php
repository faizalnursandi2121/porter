<?php

namespace App\Models;

use Database\Factories\SiteConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $site_id
 * @property string $kind
 * @property string $provider
 * @property bool $is_active
 */
class SiteConnection extends Model
{
    /** @use HasFactory<SiteConnectionFactory> */
    use HasFactory;

    public const PRIMARY = 'PRIMARY';

    public const BACKUP = 'BACKUP';

    protected $fillable = ['site_id', 'kind', 'provider', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
