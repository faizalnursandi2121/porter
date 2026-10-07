<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-27: >=1 photo per received-goods record; private disk paths (FR-48/49 access).
    public function up(): void
    {
        Schema::create('inventory_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->index('inventory_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_photos');
    }
};
