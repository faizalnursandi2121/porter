<?php

namespace App\Models;

use Database\Factories\ReportSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FR-16/18/19/25: one row per report per template section; payload holds the section's
 * answers as JSON (schema-free by design — template versions differ); stable core fields
 * the dashboard compares live inside payload.
 *
 * @property int $daily_report_id
 * @property int $section_template_id
 * @property array|null $payload
 * @property string|null $explanation
 */
class ReportSection extends Model
{
    /** @use HasFactory<ReportSectionFactory> */
    use HasFactory;

    protected $fillable = ['daily_report_id', 'section_template_id', 'payload', 'explanation'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function sectionTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportSectionTemplate::class, 'section_template_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ReportPhoto::class);
    }
}
