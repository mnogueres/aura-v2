<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FASE 20.5: Treatment catalog (read model / projection)
     * Optimized for UI queries - alphabetically ordered for selection dropdowns.
     * Historical data preserved (no hard deletes, only active flag).
     */
    public function up(): void
    {
        Schema::create('clinical_treatment_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('clinic_id');
            $table->string('name');
            $table->decimal('default_price', 10, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('projected_at');
            $table->string('source_event_id')->nullable();

            // Indexes for fast queries
            $table->index('clinic_id');
            $table->index(['clinic_id', 'active']); // Filter active definitions
            $table->index(['clinic_id', 'active', 'name']); // Alphabetical selection
            $table->index('name'); // Search by name

            // Foreign keys
            $table->foreign('clinic_id')->references('id')->on('clinics')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_treatment_definitions');
    }
};
