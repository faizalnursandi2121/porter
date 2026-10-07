<?php

namespace App\Models;

use Database\Factories\DailyReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * FR-21/22/22a/24/25: draft unnumbered; submit issues CMX.WR.YYYYMM.SEQ (unique forever);
 * status Draft|Submitted|Needs Revision|Reopened… per story-contract §3 codes; template_id +
 * timezone snapshot keep historical meaning after master changes (FR-25, FR-7).
 *
 * @property int $user_id
 * @property int $site_id
 * @property int $template_id
 * @property int|null $attendance_id
 * @property Carbon $work_date_local
 * @property string $timezone
 * @property string $status
 * @property string|null $report_number
 * @property int $revision_count
 * @property string|null $reopen_reason
 * @property Carbon|null $submitted_at
 */
class DailyReport extends Model
{
    /** @use HasFactory<DailyReportFactory> */
    use HasFactory;

    public const DRAFT = 'Draft';

    public const SUBMITTED = 'Submitted';

    public const NEEDS_REVISION = 'Needs Revision';

    public const REOPENED = 'Reopened';

    protected $fillable = [
        'user_id', 'site_id', 'template_id', 'attendance_id', 'work_date_local', 'timezone',
        'status', 'report_number', 'revision_count', 'reopen_reason', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'work_date_local' => 'date',
            'submitted_at' => 'datetime',
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

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ReportSection::class);
    }
}
