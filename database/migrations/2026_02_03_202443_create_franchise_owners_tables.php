<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Operators Entity (The profile details of a Franchise Owner)
        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tin_number')->nullable();
            $table->timestamps();
        });

        // 2. Ownership Entity (History of transfers)
        // Note: Usually this links to a specific Franchise Unit, but based on your
        // prompt, we are linking the transfer event between operators.
        Schema::create('ownerships', function (Blueprint $table) {
            $table->id();
            
            // The New Owner
            $table->unsignedBigInteger('new_operator_id');
            $table->foreign('new_operator_id')->references('id')->on('operators');

            // The Old Owner (Nullable if it's a brand new franchise)
            $table->unsignedBigInteger('previous_operator_id')->nullable();
            $table->foreign('previous_operator_id')->references('id')->on('operators');

            $table->date('date_transferred');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownerships');
        Schema::dropIfExists('operators');
    }
};