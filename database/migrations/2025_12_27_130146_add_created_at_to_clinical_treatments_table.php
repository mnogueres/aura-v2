<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clinical_treatments', function (Blueprint $table) {
            // Add created_at to preserve chronological order of treatments
            // Update sets created_at = projected_at for existing records
            $table->timestamp('created_at')->after('projected_at')->nullable();
        });

        // Backfill existing records with projected_at as created_at
        DB::table('clinical_treatments')->update(['created_at' => DB::raw('projected_at')]);

        // Make created_at non-nullable
        Schema::table('clinical_treatments', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinical_treatments', function (Blueprint $table) {
            $table->dropColumn('created_at');
        });
    }
};
