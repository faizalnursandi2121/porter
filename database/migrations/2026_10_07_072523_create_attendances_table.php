<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-7/8/9: UTC event timestamps plus school-local date; one row per EOS per local date (FR-8).
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->date('work_date_local');
            $table->timestamp('checked_in_at', 6)->nullable();
            $table->timestamp('checked_out_at', 6)->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_in_accuracy_m', 8, 2)->nullable();
            $table->string('check_in_selfie_path')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_accuracy_m', 8, 2)->nullable();
            $table->string('check_out_selfie_path')->nullable();
            $table->string('status', 24)->default('CheckedIn');
            $table->timestamps();

            $table->unique(['user_id', 'work_date_local']);
            $table->index('site_id');
            $table->index('work_date_local');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
