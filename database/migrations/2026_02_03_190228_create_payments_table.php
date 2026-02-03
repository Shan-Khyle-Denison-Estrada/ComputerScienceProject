<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Assessment Foreign Key (Nullable as requested)
            // Note: Once you have an 'assessments' table, you can uncomment the foreign constraint
            $table->unsignedBigInteger('assessment_id')->nullable();
            // $table->foreign('assessment_id')->references('id')->on('assessments')->nullOnDelete();

            $table->decimal('amount_paid', 10, 2);

            // Payee Details
            $table->string('payee_first_name');
            $table->string('payee_middle_name')->nullable();
            $table->string('payee_last_name');
            $table->string('payee_contact_number')->nullable();
            
            // Payee Address
            $table->string('payee_street_address')->nullable();
            $table->string('payee_barangay')->nullable();
            $table->string('payee_city')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};