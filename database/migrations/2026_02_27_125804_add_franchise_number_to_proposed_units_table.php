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
            $table->string('franchise_number')
                  ->nullable()
                  ->after('application_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposed_units', function (Blueprint $table) {
            $table->dropColumn('franchise_number');
        });
    }
};