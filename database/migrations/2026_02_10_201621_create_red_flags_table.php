<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Nature of Red Flags (Configuration Entity)
        Schema::create('nature_of_red_flags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // 2. Red Flags (Transactional Entity)
        Schema::create('red_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_id')->constrained()->onDelete('cascade');
            $table->foreignId('nature_id')->constrained('nature_of_red_flags');
            $table->text('remarks')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'resolved'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('red_flags');
        Schema::dropIfExists('nature_of_red_flags');
    }
};