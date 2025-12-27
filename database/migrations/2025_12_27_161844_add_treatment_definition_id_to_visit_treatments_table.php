<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FASE 20.5: Add optional reference to treatment catalog
     * Nullable to maintain compatibility with existing data and manual entry workflow.
     * The 'type' field remains the source of truth (snapshot from catalog at creation time).
     */
    public function up(): void
    {
        Schema::table('visit_treatments', function (Blueprint $table) {
            $table->uuid('treatment_definition_id')->nullable()->after('visit_id');

            // Index for queries
            $table->index('treatment_definition_id');

            // Foreign key (nullable - manual treatments won't have this)
            $table->foreign('treatment_definition_id')
                  ->references('id')
                  ->on('treatment_definitions')
                  ->onDelete('set null'); // Historical data preserved even if definition deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_treatments', function (Blueprint $table) {
            $table->dropForeign(['treatment_definition_id']);
            $table->dropIndex(['treatment_definition_id']);
            $table->dropColumn('treatment_definition_id');
        });
    }
};
