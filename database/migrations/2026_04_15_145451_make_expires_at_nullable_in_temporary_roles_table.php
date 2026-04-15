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
        Schema::table('temporary_roles', function (Blueprint $table) {
            // Make the column nullable. 
            // Note: If you used 'timestamp' originally instead of 'dateTime', change this to $table->timestamp(...)
            $table->dateTime('expires_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temporary_roles', function (Blueprint $table) {
            // Revert back to not nullable
            $table->dateTime('expires_at')->nullable(false)->change();
        });
    }
};