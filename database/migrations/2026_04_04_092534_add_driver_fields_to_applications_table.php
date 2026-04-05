<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('driver_license_number')->nullable();
            $table->date('driver_license_expiration_date')->nullable();
            $table->string('driver_user_photo')->nullable();
            $table->string('driver_license_front_photo')->nullable();
            $table->string('driver_license_back_photo')->nullable();
        });
    }

    public function down()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'driver_license_number',
                'driver_license_expiration_date',
                'driver_user_photo',
                'driver_license_front_photo',
                'driver_license_back_photo'
            ]);
        });
    }
};