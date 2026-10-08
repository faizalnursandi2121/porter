<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-3: one active assignment per EOS and one active EOS per site; history preserved.
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'site_id']);
        });

        // Fluent ->unique(...)->whereNull() silently drops the WHERE clause; raw DDL is authoritative.
        DB::statement('CREATE UNIQUE INDEX assignments_one_active_per_eos ON assignments (user_id) WHERE ended_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX assignments_one_active_per_site ON assignments (site_id) WHERE ended_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
