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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('clinic_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('patient_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            $table->decimal('amount', 10, 2);

            $table->date('payment_date');

            $table->enum('payment_method', [
                'cash',
                'card',
                'transfer',
                'other'
            ])->default('cash');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index(['clinic_id', 'patient_id']);
            $table->index(['clinic_id', 'invoice_id']);
            $table->index(['clinic_id', 'payment_date']);
            $table->index(['clinic_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
