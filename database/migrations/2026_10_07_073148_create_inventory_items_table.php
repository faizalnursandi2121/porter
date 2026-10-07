<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-27/28/31: goods bound to the school, not the EOS; status set per FR-28 with
    // mandatory reason for rusak/hilang (app-level rule; column always present).
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('category', 64);
            $table->unsignedInteger('quantity')->default(1);
            $table->date('received_at');
            $table->string('status', 24)->default('dipakai');
            $table->text('status_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
