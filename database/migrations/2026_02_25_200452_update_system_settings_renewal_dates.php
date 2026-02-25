<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->renameColumn('annual_renewal_due', 'annual_renewal_due');
            $table->string('annual_renewal_start')->nullable()->after('annual_renewal_due'); // "after" references the old name if the driver processes rename later, but better to just use nullable()
        });
    }

    public function down()
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->renameColumn('annual_renewal_due', 'fiscal_year_end');
            $table->dropColumn('annual_renewal_start');
        });
    }
};