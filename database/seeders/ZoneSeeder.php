<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // Added this to manage foreign keys

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Disable foreign key checks to bypass the constraint error
        Schema::disableForeignKeyConstraints();

        // 2. Clear existing records to prevent duplication errors during presentation resets
        DB::table('zones')->truncate();

        // 3. Re-enable foreign key checks immediately after truncating
        Schema::enableForeignKeyConstraints();

        $zones = [
            [
                'description' => 'I', // Roman numerals only, as per regex validation
                'color' => 'Blue',
                'coverage' => [
                    'Zone I', 'Malagutay', 'Calarian', 'San Roque', 'Baliwasan', 
                    'Santo Niño', 'Campo Islam', 'San Jose Cawa-Cawa', 'San Jose Gusu', 
                    'Sinunuc', 'Maasin', 'Cawit', 'Recodo'
                ],
            ],
            [
                'description' => 'II',
                'color' => 'Green',
                'coverage' => [
                    'Zone II', 'Camino Nuevo', 'Canelar', 'Santa Maria', 
                    'Pasonanca', 'Cabatangan', 'Lumayang', 'Pula Bato', 
                    'Capisan', 'Dulian (Upper Pasonanca)'
                ],
            ],
            [
                'description' => 'III',
                'color' => 'Yellow',
                'coverage' => [
                    'Zone III', 'Lunzuran', 'Lumbangan', 'Mercedes', 'Guiwan', 
                    'Tetuan', 'Tugbungan', 'Tumaga', 'Divisoria', 'Putik', 
                    'Boalan', 'Zambowood', 'Culianan', 'Pasobolong', 'Guisao', 'Cabaluay'
                ],
            ],
            [
                'description' => 'IV',
                'color' => 'Red',
                'coverage' => [
                    'Zone IV', 'Mariki', 'Rio Hondo', 'Santa Catalina', 
                    'Santa Barbara', 'Talon-Talon', 'Mampang', 'Arena Blanco', 
                    'Kasanyangan', 'Asinan'
                ],
            ],
            [
                'description' => 'V',
                'color' => 'Orange',
                'coverage' => [
                    'Manicahan', 'Victoria', 'Sangali', 'Bolong', 'Panubigan', 'Bunguiao'
                ],
            ],
            [
                'description' => 'VI',
                'color' => 'Brown',
                'coverage' => [
                    'Buluan', 'Curuan', 'Buenavista', 'Lubigan', 'Quiniput', 
                    'Muti', 'Guanan', 'Dita', 'Lapacan'
                ],
            ],
            [
                'description' => 'VII',
                'color' => 'Cyan',
                'coverage' => [
                    'Vitali', 'Tictapul', 'Mangusu', 'Tigbalabag', 'Tagasilay', 
                    'Sibulao', 'Licomo'
                ],
            ],
            [
                'description' => 'VIII',
                'color' => 'Violet',
                'coverage' => [
                    'Ayala', 'Tulungatung', 'Talise', 'Pamucutan', 'La Paz', 
                    'Baluno', 'Talisayan', 'Patalon', 'Labuan', 'Sinubong', 'Limpapa'
                ],
            ],
        ];

        foreach ($zones as $zoneData) {
            Zone::create([
                'description' => $zoneData['description'],
                'color'       => $zoneData['color'],
                'coverage'    => $zoneData['coverage'], // Model's $casts will handle JSON conversion automatically
            ]);
        }
    }
}