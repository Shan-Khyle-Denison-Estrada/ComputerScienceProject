<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_id')->constrained()->onDelete('cascade');
            
            // Core Details
            $table->string('nature_of_complaint'); // Overcharging, Discourtesy, etc.
            $table->text('remarks')->nullable();
            
            // Incident Specifics
            $table->decimal('fare_collected', 10, 2)->nullable();
            $table->string('pick_up_point')->nullable();
            $table->string('drop_off_point')->nullable();
            
            // Complainant Info
            $table->string('complainant_contact');
            
            // Timing
            $table->date('incident_date');
            $table->time('incident_time');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};