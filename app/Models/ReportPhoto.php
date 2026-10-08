<?php

namespace App\Models;

use Database\Factories\ReportPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FR-20a: report photos live on the private disk; path never public; access only via
 * the audited streaming endpoint (FR-48/49). Selfie photos are attendance columns, not rows here.
 *
 * @property int $report_section_id
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 */
class ReportPhoto extends Model
{
    /** @use HasFactory<ReportPhotoFactory> */
    use HasFactory;

    protected $fillable = ['report_section_id', 'disk', 'path', 'mime_type', 'size_bytes'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(ReportSection::class, 'report_section_id');
    }
}
