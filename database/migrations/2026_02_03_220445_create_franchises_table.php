<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create Franchises Table
        Schema::create('franchises', function (Blueprint $table) {
            $table->id();
            // These are nullable initially because we create the history records *after* the franchise ID exists
            $table->unsignedBigInteger('ownership_id')->nullable(); 
            $table->unsignedBigInteger('active_unit_id')->nullable();
            
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('zone_id')->constrained();
            
            $table->date('date_issued');
            $table->string('status')->default('active'); // active, revoked, expired
            $table->string('qr_code')->nullable(); // String or Path
            $table->timestamps();
        });

        // 2. Add franchise_id to history tables (Necessary to track history per franchise)
        Schema::table('ownerships', function (Blueprint $table) {
            $table->foreignId('franchise_id')->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('active_units', function (Blueprint $table) {
            $table->foreignId('franchise_id')->after('id')->constrained()->cascadeOnDelete();
        });

        // 3. Add Constraints back to Franchise (Circular reference handling)
        Schema::table('franchises', function (Blueprint $table) {
            $table->foreign('ownership_id')->references('id')->on('ownerships');
            $table->foreign('active_unit_id')->references('id')->on('active_units');
        });
    }

    public function down(): void
    {
        Schema::table('franchises', function (Blueprint $table) {
            $table->dropForeign(['ownership_id']);
            $table->dropForeign(['active_unit_id']);
        });
        Schema::table('ownerships', function (Blueprint $table) {
            $table->dropForeign(['franchise_id']);
            $table->dropColumn('franchise_id');
        });
        Schema::table('active_units', function (Blueprint $table) {
            $table->dropForeign(['franchise_id']);
            $table->dropColumn('franchise_id');
        });
        Schema::dropIfExists('franchises');
    }
};