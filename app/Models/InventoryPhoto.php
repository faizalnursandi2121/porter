<?php

namespace App\Models;

use Database\Factories\InventoryPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $inventory_item_id
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 */
class InventoryPhoto extends Model
{
    /** @use HasFactory<InventoryPhotoFactory> */
    use HasFactory;

    protected $fillable = ['inventory_item_id', 'disk', 'path', 'mime_type', 'size_bytes'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
