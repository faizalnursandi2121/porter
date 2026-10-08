<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-46/47: append-only audit trail — actor, role, action, object, UTC + local time,
    // before/after jsonb; no updated_at by design; app never updates or deletes rows.
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 32);
            $table->string('action', 64);
            $table->string('object_type', 64);
            $table->unsignedBigInteger('object_id')->nullable();
            $table->timestamp('occurred_at', 6);
            $table->date('occurred_date_local');
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();

            $table->index(['object_type', 'object_id']);
            $table->index(['actor_id', 'occurred_at']);
            $table->index('action');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
