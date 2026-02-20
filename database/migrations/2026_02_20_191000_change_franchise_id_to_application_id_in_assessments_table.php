<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assessments', function (Blueprint $table) {
            // 1. REMOVED: $table->dropForeign(['franchise_id']); because it never existed
            
            // 2. Just rename the column directly
            $table->renameColumn('franchise_id', 'application_id');
            
            // 3. Add the new foreign key pointing to applications
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('assessments', function (Blueprint $table) {
            // 1. Drop the new application foreign key
            $table->dropForeign(['application_id']);
            
            // 2. Rename the column back
            $table->renameColumn('application_id', 'franchise_id');
            
            // 3. (We don't re-add the franchise_id foreign key because it was never there to begin with)
        });
    }
};