<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('clinic_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event_name')->index();
            $table->json('payload');
            $table->timestamp('occurred_at');
            $table->timestamp('recorded_at');
            $table->string('status')->default('pending')->index(); // pending, processed, failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['clinic_id', 'event_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_outbox');
    }
};
