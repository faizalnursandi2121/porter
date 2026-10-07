<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('provider');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['site_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_connections');
    }
};
