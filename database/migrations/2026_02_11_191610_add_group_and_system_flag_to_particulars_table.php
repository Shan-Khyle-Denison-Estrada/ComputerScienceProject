<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('particulars', function (Blueprint $table) {
            // Grouping for fees
            $table->string('group')->nullable()->after('amount')
                ->comment('renewal, change_of_unit, change_of_owner, penalty, or null');
            
            // To identify system rows like Surcharge/Interest
            $table->string('code')->nullable()->unique()->after('name'); 
            
            // Protect from deletion
            $table->boolean('is_system')->default(false)->after('description');
        });

        // Seed the required System Particulars
        DB::table('particulars')->insert([
            [
                'name' => 'Surcharge',
                'code' => 'surcharge',
                'description' => '25% of Renewal Fees',
                'amount' => 0, // Calculated dynamically
                'group' => 'penalty',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Interest',
                'code' => 'interest',
                'description' => '2% per month on Renewal Fees',
                'amount' => 0, // Calculated dynamically
                'group' => 'penalty',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    public function down()
    {
        Schema::table('particulars', function (Blueprint $table) {
            $table->dropColumn(['group', 'code', 'is_system']);
        });
    }
};