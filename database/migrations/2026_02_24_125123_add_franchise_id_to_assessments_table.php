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
        Schema::table('assessments', function (Blueprint $table) {
            // We make it nullable() so it doesn't crash if you already have existing rows in your assessments table
            $table->foreignId('franchise_id')
                  ->nullable()
                  ->after('application_id') // Places the column right after application_id
                  ->constrained('franchises')
                  ->onDelete('cascade'); // Or 'set null' depending on how you want to handle deleted franchises
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            // Drop the foreign key constraint first, then the column
            $table->dropForeign(['franchise_id']);
            $table->dropColumn('franchise_id');
        });
    }
};