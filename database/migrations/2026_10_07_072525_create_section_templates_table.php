<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-14: five ordered sections; order changes with template revisions.
    public function up(): void
    {
        Schema::create('section_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['template_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_templates');
    }
};
