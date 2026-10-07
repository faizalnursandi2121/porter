<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-25: each report snapshots its template via section_template_id; stable core fields
    // (uptime, cpu_ram_pct, ap_offline_count, temperature_c, connectivity json) serve KPI comparison.
    public function up(): void
    {
        Schema::create('report_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_template_id')->constrained('section_templates')->restrictOnDelete();
            $table->jsonb('payload')->nullable();
            $table->text('explanation')->nullable();
            $table->timestamps();

            $table->unique(['daily_report_id', 'section_template_id']);
        });

        DB::statement('CREATE INDEX report_sections_payload_gin ON report_sections USING GIN (payload)');
    }

    public function down(): void
    {
        Schema::dropIfExists('report_sections');
    }
};
