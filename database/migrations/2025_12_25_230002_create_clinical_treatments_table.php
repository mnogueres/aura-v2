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
        Schema::create('clinical_treatments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('clinic_id')->index();
            $table->unsignedBigInteger('patient_id')->index();
            $table->uuid('visit_id')->index();
            $table->string('type');
            $table->string('tooth')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('projected_at');
            $table->uuid('source_event_id')->nullable()->index();

            $table->index(['clinic_id', 'patient_id']);
            $table->index(['clinic_id', 'visit_id']);
            $table->foreign('visit_id')->references('id')->on('clinical_visits')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_treatments');
    }
};
