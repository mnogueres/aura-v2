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
        Schema::table('visits', function (Blueprint $table) {
            // Drop existing foreign key
            $table->dropForeign(['professional_id']);

            // Change column type to uuid
            $table->uuid('professional_id')->nullable()->change();

            // Add new foreign key to professionals table
            $table->foreign('professional_id')->references('id')->on('professionals')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            // Drop new foreign key
            $table->dropForeign(['professional_id']);

            // Change back to unsignedBigInteger
            $table->unsignedBigInteger('professional_id')->nullable()->change();

            // Restore old foreign key to users
            $table->foreign('professional_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
