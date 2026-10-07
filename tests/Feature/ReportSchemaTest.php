<?php

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\ReportPhoto;
use App\Models\ReportSection;
use App\Models\ReportSectionTemplate;
use App\Models\ReportTemplate;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;

test('assignment enforces one active per EOS', function () {
    $eos = User::factory()->withRole(Role::EOS)->create();
    Assignment::factory()->create(['user_id' => $eos->id]);

    expect(fn () => Assignment::factory()->create(['user_id' => $eos->id]))
        ->toThrow(RuntimeException::class);
});

test('assignment enforces one active EOS per site', function () {
    $site = Site::factory()->create();
    Assignment::factory()->create(['site_id' => $site->id]);

    $otherEos = User::factory()->withRole(Role::EOS)->create();
    expect(fn () => Assignment::factory()->create(['site_id' => $site->id, 'user_id' => $otherEos->id]))
        ->toThrow(RuntimeException::class);
});

test('sequential assignments allowed after previous ends', function () {
    $eos = User::factory()->withRole(Role::EOS)->create();
    $site = Site::factory()->create();
    Assignment::factory()->ended()->create(['user_id' => $eos->id, 'site_id' => $site->id]);

    $second = Assignment::factory()->create(['user_id' => $eos->id, 'site_id' => $site->id]);

    expect($second->exists)->toBeTrue()
        ->and(Assignment::where('user_id', $eos->id)->whereNull('ended_at')->count())->toBe(1);
});

test('attendance is unique per EOS per local date', function () {
    $attendance = Attendance::factory()->create();

    expect(fn () => Attendance::factory()->create([
        'user_id' => $attendance->user_id,
        'work_date_local' => $attendance->work_date_local,
    ]))->toThrow(RuntimeException::class);
});

test('attendance can be completed and holds checkout evidence', function () {
    $attendance = Attendance::factory()->completed()->create();

    expect($attendance->status)->toBe(Attendance::COMPLETED)
        ->and($attendance->checked_out_at)->not->toBeNull()
        ->and($attendance->check_out_selfie_path)->toStartWith('attendances/');
});

test('draft report has no number; submitted report number matches CMX format', function () {
    $draft = DailyReport::factory()->create();
    $submitted = DailyReport::factory()->submitted()->create();

    expect($draft->report_number)->toBeNull()
        ->and($draft->status)->toBe(DailyReport::DRAFT)
        ->and($submitted->report_number)->toMatch('/^CMX\.WR\.\d{6}\.\d{4}$/')
        ->and($submitted->submitted_at)->not->toBeNull();
});

test('report numbers are globally unique', function () {
    $number = 'CMX.WR.202610.0001';
    DailyReport::factory()->submitted()->create(['report_number' => $number]);

    expect(fn () => DailyReport::factory()->submitted()->create(['report_number' => $number]))
        ->toThrow(RuntimeException::class);
});

test('report has one section per template section', function () {
    $sectionTemplate = ReportSectionTemplate::factory()->create();
    $report = DailyReport::factory()->create(['template_id' => $sectionTemplate->template_id]);
    ReportSection::factory()->create([
        'daily_report_id' => $report->id,
        'section_template_id' => $sectionTemplate->id,
    ]);

    expect(fn () => ReportSection::factory()->create([
        'daily_report_id' => $report->id,
        'section_template_id' => $sectionTemplate->id,
    ]))->toThrow(RuntimeException::class);
});

test('deleting a report cascades through sections to photos', function () {
    $section = ReportSection::factory()->create();
    $photo = ReportPhoto::factory()->create(['report_section_id' => $section->id]);
    $report = $section->dailyReport;

    $report->delete();

    expect(ReportSection::find($section->id))->toBeNull()
        ->and(ReportPhoto::find($photo->id))->toBeNull();
});

test('report keeps template and timezone snapshot references', function () {
    $report = DailyReport::factory()->timezone('Asia/Jayapura')->create();

    expect($report->template)->toBeInstanceOf(ReportTemplate::class)
        ->and($report->timezone)->toBe('Asia/Jayapura')
        ->and($report->site->timezone)->not->toBeNull();
});

test('section template orders within its template', function () {
    $template = ReportTemplate::factory()->create();
    $first = ReportSectionTemplate::factory()->create(['template_id' => $template->id, 'order' => 1]);
    $second = ReportSectionTemplate::factory()->create(['template_id' => $template->id, 'order' => 2]);

    expect($template->sectionTemplates->pluck('id')->all())->toBe([$first->id, $second->id]);

    try {
        ReportSectionTemplate::factory()->create(['template_id' => $template->id, 'order' => 2]);
        $this->fail('Duplicate order accepted');
    } catch (RuntimeException $e) {
        expect($e->getPrevious()->getCode())->toBe('23505');
    }
});
