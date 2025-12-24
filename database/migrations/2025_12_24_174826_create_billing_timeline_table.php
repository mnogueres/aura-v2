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
        Schema::create('billing_timeline', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('event_name');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('event_payload');
            $table->timestamp('occurred_at');
            $table->timestamp('projected_at');
            $table->string('source_event_id')->unique();

            $table->index(['clinic_id', 'patient_id', 'occurred_at']);
            $table->index('clinic_id');
            $table->index('event_name');

            $table->foreign('clinic_id')->references('id')->on('clinics')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_timeline');
    }
};
