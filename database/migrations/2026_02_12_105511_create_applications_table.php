<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Define the requirements (e.g., "Deed of Sale", "OR/CR")
        Schema::create('application_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('application_type'); // 'new_franchise', 'renewal', 'transfer_ownership', 'change_unit', 'new_operator'
            $table->string('name');
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });

        // 2. The Application Instance
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // Who applied?
            $table->foreignId('franchise_id')->nullable()->constrained(); // Null if new franchise or new operator
            $table->foreignId('assessment_id')->nullable()->constrained(); // The auto-generated bill
            $table->string('type'); 
            $table->string('status')->default('pending'); // pending, review, approved, rejected
            $table->text('remarks')->nullable();
            
            // For 'change_unit' or 'transfer', we need to store the target IDs temporarily
            // until approved. We store them as JSON or specific fields.
            $table->json('proposed_data')->nullable(); 
            // Example: {'new_unit_id': 5} or {'new_operator_id': 10}
            
            $table->timestamps();
        });

        // 3. The Uploaded Files
        Schema::create('application_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_requirement_id')->constrained();
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('application_attachments');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('application_requirements');
    }
};