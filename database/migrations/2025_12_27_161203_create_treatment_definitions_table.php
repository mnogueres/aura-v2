<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FASE 20.5: Treatment catalog (write model)
     * Stores stable treatment definitions per clinic with reference price.
     * Price is NOT binding - actual treatment price can differ per visit.
     */
    public function up(): void
    {
        Schema::create('treatment_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('clinic_id');
            $table->string('name');
            $table->decimal('default_price', 10, 2)->nullable()->comment('Reference price, not binding');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('clinic_id');
            $table->index(['clinic_id', 'active']);
            $table->index('name');

            // Foreign keys
            $table->foreign('clinic_id')->references('id')->on('clinics')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_definitions');
    }
};
