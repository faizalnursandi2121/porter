<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-21/22/22a/24: drafts unnumbered; CMX.WR.YYYYMM.SEQ on submit, unique forever;
    // template snapshot reference (FR-25); local date + tz snapshot per FR-7.
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('template_id')->constrained('templates')->restrictOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date_local');
            $table->string('timezone', 32);
            $table->string('status', 24)->default('Draft');
            $table->string('report_number', 32)->nullable()->unique();
            $table->unsignedInteger('revision_count')->default(0);
            $table->text('reopen_reason')->nullable();
            $table->timestamp('submitted_at', 6)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'work_date_local']);
            $table->index(['site_id', 'work_date_local']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
