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
        Schema::create('patient_summary', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('patient_id');
            $table->timestamp('created_at_occurred')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->unsignedInteger('invoices_count')->default(0);
            $table->unsignedInteger('payments_count')->default(0);
            $table->decimal('total_invoiced_amount', 10, 2)->default(0);
            $table->decimal('total_paid_amount', 10, 2)->default(0);
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('projected_at')->nullable();

            $table->unique(['clinic_id', 'patient_id']);
            $table->index('clinic_id');
            $table->index('last_activity_at');

            $table->foreign('clinic_id')->references('id')->on('clinics')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_summary');
    }
};
