<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Adding a string column, usually defaulting to 'pending'
            $table->string('status')->default('pending')->after('id'); 
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Drop the column if the migration is rolled back
            $table->dropColumn('status');
        });
    }
};
