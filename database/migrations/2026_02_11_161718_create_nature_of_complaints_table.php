<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nature_of_complaints', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Ensure unique names
            $table->timestamps();
        });
        
        // Optional: Seed default data
        DB::table('nature_of_complaints')->insert([
            ['name' => 'Overcharging / Overpricing'],
            ['name' => 'Refusal to Convey Passenger'],
            ['name' => 'Reckless Driving'],
            ['name' => 'Arrogance / Discourtesy'],
            ['name' => 'Trip Cutting'],
            ['name' => 'Operating without License'],
            ['name' => 'Others'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('nature_of_complaints');
    }
};