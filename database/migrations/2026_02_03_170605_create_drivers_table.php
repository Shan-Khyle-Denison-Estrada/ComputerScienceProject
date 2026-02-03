<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            // Foreign Key (Nullable, because not all drivers are franchise owners)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            
            $table->string('contact_number')->nullable();
            
            // Address
            $table->string('street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            
            // License Details
            $table->string('license_number')->unique();
            $table->date('license_expiration_date');
            
            // Photos (Store paths)
            $table->string('user_photo')->nullable();
            $table->string('license_front_photo')->nullable();
            $table->string('license_back_photo')->nullable();
            
            $table->string('status')->default('active'); // active, inactive
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};