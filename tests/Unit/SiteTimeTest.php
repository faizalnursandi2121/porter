<?php

use App\Support\SiteTime;
use Illuminate\Support\Carbon;

// FR-7: UTC canonical; site timezone authoritative for local dates/labels.

test('converts UTC to site local time across the three tz labels', function () {
    $utc = Carbon::parse('2026-10-07 17:30:00', 'UTC');

    expect(SiteTime::toSite($utc, 'Asia/Jakarta')->format('Y-m-d H:i'))->toBe('2026-10-08 00:30')
        ->and(SiteTime::toSite($utc, 'Asia/Makassar')->format('Y-m-d H:i'))->toBe('2026-10-08 01:30')
        ->and(SiteTime::toSite($utc, 'Asia/Jayapura')->format('Y-m-d H:i'))->toBe('2026-10-08 02:30');
});

test('converts local time back to UTC losslessly', function () {
    $local = Carbon::parse('2026-10-08 08:15:00', 'Asia/Jayapura');

    $utc = SiteTime::toUtc($local, 'Asia/Jayapura');

    expect($utc->format('Y-m-d H:i'))->toBe('2026-10-07 23:15')
        ->and($utc->timezoneName)->toBe('UTC');
});

test('utc to local to utc round-trips exactly', function () {
    $utc = Carbon::parse('2026-03-15 09:45:00', 'UTC');

    $back = SiteTime::toUtc(SiteTime::toSite($utc, 'Asia/Makassar'), 'Asia/Makassar');

    expect($back->equalTo($utc))->toBeTrue();
});

test('local date differs across timezones for the same instant', function () {
    $utc = Carbon::parse('2026-10-07 17:30:00', 'UTC');

    expect(SiteTime::localDate($utc, 'Asia/Jakarta'))->toBe('2026-10-08')
        ->and(SiteTime::localDate($utc->copy()->subHours(8), 'Asia/Jakarta'))->toBe('2026-10-07');
});

test('local label renders time with tz abbreviation', function () {
    $utc = Carbon::parse('2026-10-07 02:05:00', 'UTC');

    expect(SiteTime::localLabel($utc, 'Asia/Jakarta'))->toBe('09:05 WIB');
});

test('tz label mapping covers WIB WITA WIT', function () {
    expect(SiteTime::tzLabelToIana('WIB'))->toBe('Asia/Jakarta')
        ->and(SiteTime::tzLabelToIana('WITA'))->toBe('Asia/Makassar')
        ->and(SiteTime::tzLabelToIana('WIT'))->toBe('Asia/Jayapura')
        ->and(SiteTime::tzLabelToIana('Asia/Jakarta'))->toBe('Asia/Jakarta');
});

test('respects test-now for deterministic now-derived values', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-07 17:00:00', 'UTC'));

    expect(SiteTime::localDate(Carbon::now(), 'Asia/Jayapura'))->toBe('2026-10-08');
});
