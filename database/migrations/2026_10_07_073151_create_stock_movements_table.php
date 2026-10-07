<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-29: immutable ledger — before/after balances recorded at write time; movements are
    // never updated (corrections are new movements; FR-32 audit trail covers the actor).
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_stock_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type', 24);
            $table->integer('quantity');
            $table->unsignedInteger('before_balance');
            $table->unsignedInteger('after_balance');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['material_stock_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
