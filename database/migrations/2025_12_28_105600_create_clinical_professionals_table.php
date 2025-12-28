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
        Schema::create('clinical_professionals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('clinic_id')->index();
            $table->string('name');
            $table->string('role');
            $table->boolean('active')->default(true);
            $table->timestamp('projected_at');
            $table->uuid('source_event_id')->nullable();

            // Foreign key
            $table->foreign('clinic_id')->references('id')->on('clinics')->onDelete('cascade');

            // Composite index
            $table->index(['clinic_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_professionals');
    }
};
