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
            $table->dropColumn('professional_name');
            $table->unsignedBigInteger('professional_id')->nullable()->after('patient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinical_visits', function (Blueprint $table) {
            $table->dropColumn('professional_id');
            $table->string('professional_name')->after('occurred_at');
        });
    }
};
