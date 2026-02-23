<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('evaluator_status')->default('Pending')->after('status');
            $table->string('inspector_status')->default('Pending')->after('evaluator_status');
            // Assuming capo_status is already here from our previous step
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['evaluator_status', 'inspector_status']);
        });
    }
};