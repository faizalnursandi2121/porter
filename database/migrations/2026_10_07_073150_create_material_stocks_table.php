<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-29/30: one consumable balance per school per material; balance never negative
    // enforced at DB level; no minimum thresholds this version.
    public function up(): void
    {
        Schema::create('material_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('unit', 24);
            $table->unsignedInteger('balance')->default(0);
            $table->timestamps();

            $table->unique(['site_id', 'name']);
        });

        DB::statement('ALTER TABLE material_stocks ADD CONSTRAINT material_stocks_balance_non_negative CHECK (balance >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('material_stocks');
    }
};
