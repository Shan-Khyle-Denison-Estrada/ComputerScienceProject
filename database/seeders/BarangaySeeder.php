<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            'Arena Blanco', 'Ayala', 'Baliwasan', 'Baluno', 'Boalan', 'Bolong', 'Buenavista', 
            'Bunguiao', 'Busay', 'Cabaluay', 'Cabatangan', 'Cacao', 'Calabasa', 'Calarian', 
            'Camino Nuevo', 'Campo Islam', 'Canelar', 'Capisan', 'Cawit', 'Culianan', 'Curuan', 
            'Dita', 'Divisoria', 'Dulian (Upper Bunguiao)', 'Dulian (Upper Pasonanca)', 'Guisao', 
            'Guiwan', 'Kasanyangan', 'La Paz', 'Labuan', 'Lamisahan', 'Landang Gua', 'Landang Laum', 
            'Lanzones', 'Lapakan', 'Latuan', 'Licomo', 'Limaong', 'Limpapa', 'Lubigan', 'Lumayang', 
            'Lumbangan', 'Lunzuran', 'Maasin', 'Malagutay', 'Mampang', 'Manalipa', 'Mangusu', 
            'Manicahan', 'Mariki', 'Mercedes', 'Muti', 'Pamucutan', 'Pangapuyan', 'Panubigan', 
            'Pasilmanta', 'Pasobolong', 'Pasonanca', 'Patalon', 'Putik', 'Quiniput', 'Recodo', 
            'Rio Hondo', 'Salaan', 'San Jose Cawa-cawa', 'San Jose Gusu', 'San Roque', 'Sangali', 
            'Sibulao', 'Sinubong', 'Sinunuc', 'Sta. Barbara', 'Sta. Catalina', 'Sta. Maria', 
            'Sto. Niño', 'Tagasilay', 'Taguiti', 'Talabaan', 'Talisayan', 'Taluksangay', 'Tetuan', 
            'Tictabon', 'Tictapul', 'Tigbalabag', 'Tigtabon', 'Tolosa', 'Tugbungan', 'Tulungatung', 
            'Tumaga', 'Tumalutab', 'Tumitus', 'Victoria', 'Vitali', 'Zambowood', 'Zone I', 
            'Zone II', 'Zone III', 'Zone IV'
        ];

        foreach ($barangays as $name) {
            Barangay::firstOrCreate(['name' => $name]);
        }
    }
}