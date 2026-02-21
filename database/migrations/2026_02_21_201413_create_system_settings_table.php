<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            
            // Operations
            $table->string('fiscal_year_end')->default('01-01');
            $table->decimal('surcharge_rate', 5, 2)->default(25.00);
            $table->decimal('interest_rate', 5, 2)->default(2.00);
            
            // Branding & Appearance
            $table->string('theme_color')->default('#3B82F6');
            $table->string('lgu_name')->nullable();
            $table->string('lgu_logo_path')->nullable();
            $table->string('office_name')->nullable();
            $table->string('office_logo_path')->nullable();
            $table->string('hero_image_path')->nullable();
            
            // Public Content
            $table->string('contact_number')->nullable();
            $table->string('address')->nullable();
            $table->text('about_us')->nullable();
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();
            $table->json('ordinances')->nullable();
            $table->json('faqs')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};