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
        Schema::create('audit_trail', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->string('event_name');
            $table->enum('category', ['platform', 'security', 'billing', 'crm'])->default('platform');
            $table->enum('severity', ['info', 'warning', 'error'])->default('info');
            $table->enum('actor_type', ['system', 'user'])->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('context');
            $table->timestamp('occurred_at');
            $table->timestamp('projected_at');
            $table->string('source_event_id')->unique();

            $table->index(['clinic_id', 'occurred_at']);
            $table->index('event_name');
            $table->index('severity');

            $table->foreign('clinic_id')->references('id')->on('clinics')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trail');
    }
};
