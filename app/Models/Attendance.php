<?php

namespace App\Models;

use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * FR-7/8/9: one row per EOS per school-local date; UTC event timestamps; selfie paths
 * point into the private disk (FR-5b watermark applied at render time, not storage).
 *
 * @property int $user_id
 * @property int $site_id
 * @property Carbon $work_date_local
 * @property Carbon|null $checked_in_at
 * @property Carbon|null $checked_out_at
 * @property string $status
 */
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    public const CHECKED_IN = 'CheckedIn';

    public const COMPLETED = 'Completed';

    protected $fillable = [
        'user_id', 'site_id', 'work_date_local',
        'checked_in_at', 'check_in_latitude', 'check_in_longitude', 'check_in_accuracy_m', 'check_in_selfie_path',
        'checked_out_at', 'check_out_latitude', 'check_out_longitude', 'check_out_accuracy_m', 'check_out_selfie_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'work_date_local' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'check_in_latitude' => 'float',
            'check_in_longitude' => 'float',
            'check_in_accuracy_m' => 'float',
            'check_out_latitude' => 'float',
            'check_out_longitude' => 'float',
            'check_out_accuracy_m' => 'float',
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

    // FK lives on daily_reports.attendance_id (report references the day's attendance).
    public function dailyReport(): HasOne
    {
        return $this->hasOne(DailyReport::class, 'attendance_id');
    }
}
