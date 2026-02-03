<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Particulars (Master List of Fees)
        Schema::create('particulars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });

        // 2. Assessments (Header)
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('franchise_id')->nullable(); 
            // $table->foreign('franchise_id')->references('id')->on('franchises'); // Uncomment if franchise table exists
            
            $table->date('assessment_date');
            $table->date('assessment_due');
            $table->decimal('total_amount_due', 10, 2)->default(0);
            $table->string('assessment_status')->default('pending'); // pending, paid, overdue
            $table->text('remarks')->nullable();
            $table->date('date_approved')->nullable();
            $table->timestamps();
        });

        // 3. Assessment Particulars (Pivot/Line Items)
        Schema::create('assessment_particulars', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('assessment_id')->constrained()->onDelete('cascade');
            $table->foreignId('particular_id')->constrained()->onDelete('cascade');
            
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 10, 2);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_particulars');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('particulars');
    }
};