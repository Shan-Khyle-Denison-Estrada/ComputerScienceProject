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
        Schema::table('proposed_units', function (Blueprint $table) {
            // Add date_issued as a date column. 
            // Setting it as nullable is safe if you have existing records.
            $table->date('date_issued')->nullable()->after('franchise_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposed_units', function (Blueprint $table) {
            $table->dropColumn('date_issued');
        });
    }
};