<?php

namespace App\Support;

use App\Models\Site;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * FR-7: UTC is canonical in storage; site timezone is authoritative for local dates.
 * Legacy §10: server clock trusted, client clock never; tests use Carbon::setTestNow().
 */
class SiteTime
{
    public static function toSite(CarbonInterface $utc, string $timezone): Carbon
    {
        return Carbon::instance($utc)->setTimezone($timezone);
    }

    public static function toUtc(CarbonInterface $local, string $timezone): Carbon
    {
        return Carbon::instance($local)->setTimezone($timezone)->utc();
    }

    public static function localDate(CarbonInterface $utc, string $timezone): string
    {
        return self::toSite($utc, $timezone)->toDateString();
    }

    public static function localLabel(CarbonInterface $utc, string $timezone): string
    {
        return self::toSite($utc, $timezone)->format('H:i T');
    }

    public static function tzLabelToIana(string $tzLabel): string
    {
        return Site::TIMEZONES[$tzLabel] ?? $tzLabel;
    }
}
