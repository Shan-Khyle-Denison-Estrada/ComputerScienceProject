<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Unit Makes (e.g., Honda, Kawasaki, TVS)
        Schema::create('unit_makes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Honda TMX 125"
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Units (The Physical Tricycle)
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('make_id')->constrained('unit_makes')->onDelete('cascade');
            
            $table->string('plate_number')->unique();
            $table->string('motor_number')->unique();
            $table->string('chassis_number')->unique();
            $table->year('model_year');
            $table->string('cr_number')->nullable(); // Certificate of Registration
            
            // Images
            $table->string('unit_front_photo')->nullable();
            $table->string('unit_back_photo')->nullable();
            $table->string('unit_left_photo')->nullable();
            $table->string('unit_right_photo')->nullable();

            $table->timestamps();
        });

        // 3. Active Units (History/Replacement Log)
        Schema::create('active_units', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('previous_unit_id')->nullable();
            $table->foreign('previous_unit_id')->references('id')->on('units');

            $table->unsignedBigInteger('new_unit_id');
            $table->foreign('new_unit_id')->references('id')->on('units');

            $table->date('date_changed');
            $table->text('remarks')->nullable(); // Reason for change
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_units');
        Schema::dropIfExists('units');
        Schema::dropIfExists('unit_makes');
    }
};