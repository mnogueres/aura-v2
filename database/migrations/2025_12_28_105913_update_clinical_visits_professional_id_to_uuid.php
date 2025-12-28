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
        Schema::table('clinical_visits', function (Blueprint $table) {
            // Change column type to uuid
            $table->uuid('professional_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinical_visits', function (Blueprint $table) {
            // Change back to unsignedBigInteger
            $table->unsignedBigInteger('professional_id')->nullable()->change();
        });
    }
};
