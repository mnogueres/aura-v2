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
        Schema::table('clinical_treatments', function (Blueprint $table) {
            $table->uuid('treatment_definition_id')->nullable()->after('visit_id');
            $table->foreign('treatment_definition_id')
                ->references('id')
                ->on('treatment_definitions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinical_treatments', function (Blueprint $table) {
            $table->dropForeign(['treatment_definition_id']);
            $table->dropColumn('treatment_definition_id');
        });
    }
};
