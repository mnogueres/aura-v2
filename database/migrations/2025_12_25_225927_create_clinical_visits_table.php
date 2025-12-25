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
        Schema::create('clinical_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('clinic_id')->index();
            $table->unsignedBigInteger('patient_id')->index();
            $table->timestamp('occurred_at')->index();
            $table->string('professional_name');
            $table->string('visit_type')->nullable();
            $table->text('summary')->nullable();
            $table->unsignedInteger('treatments_count')->default(0);
            $table->timestamp('projected_at');
            $table->uuid('source_event_id')->nullable()->index();

            $table->index(['clinic_id', 'patient_id']);
            $table->index(['clinic_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_visits');
    }
};
