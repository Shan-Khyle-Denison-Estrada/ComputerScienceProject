<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('driver_assignments', function (Blueprint $table) {
            // Stores the weekly schedule as a JSON array
            $table->json('schedule')->nullable()->after('is_active');
        });
    }

    public function down()
    {
        Schema::table('driver_assignments', function (Blueprint $table) {
            $table->dropColumn('schedule');
        });
    }
};