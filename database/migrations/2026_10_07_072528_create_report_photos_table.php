<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-20/20a: photos attach to a section (NOT NULL); private disk path; mime + size recorded
    // at upload for the explicit-reason validation trail.
    public function up(): void
    {
        Schema::create('report_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_section_id')->constrained('report_sections')->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->index('report_section_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_photos');
    }
};
