<?php

namespace App\Models;

use Database\Factories\ReportSectionTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $template_id
 * @property string $name
 * @property int $order
 * @property bool $is_active
 */
class ReportSectionTemplate extends Model
{
    /** @use HasFactory<ReportSectionTemplateFactory> */
    use HasFactory;

    protected $table = 'section_templates';

    protected $fillable = ['template_id', 'name', 'order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    public function reportSections(): HasMany
    {
        return $this->hasMany(ReportSection::class);
    }
}
