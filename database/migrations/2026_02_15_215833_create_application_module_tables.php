<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. APPLICATIONS MASTER TABLE
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique(); // Generated: APP-YYYY-XXXX
            
            // Links
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // If logged in
            $table->foreignId('franchise_id')->nullable()->constrained()->onDelete('set null'); // If renewal/change
            $table->foreignId('zone_id')->nullable()->constrained()->onDelete('set null'); // Target Zone
            
            // Meta
            $table->string('application_type'); // 'New Franchise', 'Renewal', 'Change Unit', 'Change Owner'
            $table->string('status')->default('Pending'); // Pending, For Inspection, Approved, Rejected, Returned
            $table->text('remarks')->nullable(); // For "Return" or "Reject" reasons
            
            // Applicant Snapshot (Important if user doesn't exist yet)
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('contact_number');
            $table->string('email');
            $table->string('tin_number')->nullable();
            
            // Address Snapshot
            $table->string('street_address');
            $table->string('barangay');
            $table->string('city')->default('Zamboanga City');

            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // 2. PROPOSED UNITS (One Application -> Many Units)
        Schema::create('proposed_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->foreignId('make_id')->constrained('unit_makes'); // Links to your existing unit_makes
            
            $table->string('model_year');
            $table->string('plate_number')->nullable();
            $table->string('motor_number');
            $table->string('chassis_number');
            $table->string('cr_number')->nullable();

            // Photos
            $table->string('unit_front_photo')->nullable();
            $table->string('unit_back_photo')->nullable();
            $table->string('unit_left_photo')->nullable();
            $table->string('unit_right_photo')->nullable();
            $table->string('cr_photo')->nullable();
            $table->string('or_photo')->nullable();
            $table->string('franchise_certificate_photo')->nullable();

            $table->timestamps();
        });

        // 3. CONFIG: EVALUATION REQUIREMENTS (Document Checklist)
        Schema::create('evaluation_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('group')->default('General'); // 'New Franchise', 'Renewal', 'Change Unit', etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. CONFIG: INSPECTION ITEMS (Mechanical Checklist)
        Schema::create('inspection_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('rating_options')->nullable(); // e.g., ["Pass", "Fail"]
            $table->timestamps();
        });

        // 5. TRANSACTION: APPLICATION EVALUATIONS (Checklist Results)
        Schema::create('application_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->foreignId('requirement_id')->constrained('evaluation_requirements');
            $table->boolean('is_compliant')->nullable()->default(null);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // 6. TRANSACTION: UNIT INSPECTIONS (Inspection Results per Unit)
        Schema::create('unit_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposed_unit_id')->constrained()->onDelete('cascade');
            $table->foreignId('inspection_item_id')->constrained()->onDelete('cascade');
            $table->string('rating'); // 'Pass', 'Fail', 'Fair'
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_inspections');
        Schema::dropIfExists('application_evaluations');
        Schema::dropIfExists('inspection_items');
        Schema::dropIfExists('evaluation_requirements');
        Schema::dropIfExists('proposed_units');
        Schema::dropIfExists('applications');
    }
};