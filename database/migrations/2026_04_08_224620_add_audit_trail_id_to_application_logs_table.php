<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_logs', function (Blueprint $table) {
            $table->string('log_no')->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('application_logs', function (Blueprint $table) {
            $table->dropColumn('log_no');
        });
    }
};