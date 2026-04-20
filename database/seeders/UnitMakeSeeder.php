<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UnitMake;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UnitMakeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('unit_makes')->truncate();
        Schema::enableForeignKeyConstraints();

        $makes = [
            ['name' => 'Suzuki', 'description' => 'Suzuki Motor Corporation'],
            ['name' => 'Piaggio', 'description' => 'Piaggio & C. SpA'],
            ['name' => 'Honda', 'description' => 'Honda Motor Company'],
            ['name' => 'Yamaha', 'description' => 'Yamaha Motor Company'],
        ];

        foreach ($makes as $make) {
            UnitMake::create($make);
        }
    }
}