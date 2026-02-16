<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposed_units', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->after('make_id')->constrained();
        });
        
        // Optional: We keep the zone_id in 'applications' as a "Primary Zone" 
        // or we can make it nullable if strictly per unit.
        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('proposed_units', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn('zone_id');
        });
    }
};