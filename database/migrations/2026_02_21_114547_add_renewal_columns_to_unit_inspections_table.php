<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            // Add the columns needed for Renewal Inspections
            $table->foreignId('unit_id')->nullable()->constrained('units')->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('applications')->cascadeOnDelete();
            
            // Make proposed_unit_id nullable so renewals can save without it
            $table->unsignedBigInteger('proposed_unit_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['application_id']);
            $table->dropColumn(['unit_id', 'application_id']);
            
            $table->unsignedBigInteger('proposed_unit_id')->nullable(false)->change();
        });
    }
};