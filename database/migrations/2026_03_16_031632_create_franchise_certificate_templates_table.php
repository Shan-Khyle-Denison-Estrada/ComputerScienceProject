<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('franchise_certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('content')->nullable(); // TipTap content (HTML)
            $table->string('paper_size')->default('A4');
            $table->json('margins')->nullable(); // {top, bottom, left, right}
            $table->foreignId('author_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('franchise_certificate_templates');
    }
};