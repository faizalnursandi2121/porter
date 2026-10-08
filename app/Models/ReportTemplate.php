<?php

namespace App\Models;

use Database\Factories\ReportTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FR-14/25: Administrator-managed report template; only the active one serves new reports.
 * Table name `templates` per subtask 1.2; class aliased ReportTemplate to avoid the
 * mail-template collision and keep report intent explicit.
 *
 * @property string $name
 * @property bool $is_active
 * @property int|null $created_by
 */
class ReportTemplate extends Model
{
    /** @use HasFactory<ReportTemplateFactory> */
    use HasFactory;

    protected $table = 'templates';

    protected $fillable = ['name', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sectionTemplates(): HasMany
    {
        return $this->hasMany(ReportSectionTemplate::class, 'template_id')->orderBy('order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
