<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

// Models
use App\Models\Zone;
use App\Models\UnitMake;
use App\Models\User;
use App\Models\Operator;
use App\Models\Unit;
use App\Models\ActiveUnit;
use App\Models\Franchise;
use App\Models\Ownership;

class GraduationSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('ownerships')->truncate();
        DB::table('active_units')->truncate();
        DB::table('franchises')->truncate();
        DB::table('units')->truncate();
        DB::table('operators')->truncate();
        User::where('email', 'like', '%@dummyoperator.com')->delete(); 
        Schema::enableForeignKeyConstraints();

        $directories = ['qrcodes', 'users', 'units'];
        foreach ($directories as $dir) {
            if (!Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir);
            }
        }

        $zones = Zone::all();
        $unitMakes = UnitMake::all();

        if ($zones->isEmpty() || $unitMakes->isEmpty()) {
            $this->command->error('Please run ZoneSeeder and UnitMakeSeeder first!');
            return;
        }

        $operatorPool = [];
        $faker = \Faker\Factory::create('en_PH');

        $this->command->info('Setting up 25 chronologically spread out Operators...');

        // Operator dates from Jan 2024 to Dec 2025
        $opStart = Carbon::create(2024, 1, 1)->timestamp;
        $opEnd = Carbon::create(2025, 12, 31)->timestamp;

        for ($i = 1; $i <= 25; $i++) {
            $userPhotoPath = "users/operator{$i}.jpg";
            
            if (!Storage::disk('public')->exists($userPhotoPath)) {
                try {
                    $imageData = file_get_contents("https://i.pravatar.cc/300?u=operator{$i}");
                    Storage::disk('public')->put($userPhotoPath, $imageData);
                } catch (\Exception $e) {
                    $userPhotoPath = null;
                }
            }

            // Spread operator registration across 2024 and 2025
            $operatorDate = Carbon::createFromTimestamp(rand($opStart, $opEnd));

            $user = User::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => "operator{$i}@dummyoperator.com",
                'password' => Hash::make('password'), 
                'user_photo' => $userPhotoPath, 
                'contact_number' => '09' . $faker->numerify('#########'),
                'street_address' => $faker->streetAddress,
                'barangay' => 'Tetuan', 
                'city' => 'Zamboanga City',
                'province' => 'Zamboanga del Sur',
                'role' => 'franchise_owner', 
                'status' => 'Active'
            ]);
            $user->timestamps = false; $user->created_at = $operatorDate; $user->updated_at = $operatorDate; $user->save();

            $operator = Operator::create([
                'user_id' => $user->id,
                'tin_number' => $faker->numerify('###-###-###-000'),
            ]);
            $operator->timestamps = false; $operator->created_at = $operatorDate; $operator->updated_at = $operatorDate; $operator->save();

            $operatorPool[] = [
                'model' => $operator,
                'franchise_count' => 0
            ];
        }

        $zoneCounters = [];
        foreach ($zones as $zone) {
            $zoneCounters[$zone->id] = 1;
        }

        $this->command->info('Seeding 50 Franchises guaranteeing timeline accuracy...');

        for ($i = 1; $i <= 50; $i++) {
            $zone = $zones->random();
            $make = $unitMakes->random();

            $counterStr = str_pad($zoneCounters[$zone->id], 5, '0', STR_PAD_LEFT);
            $franchiseNumber = "{$zone->description}-{$counterStr}";
            $zoneCounters[$zone->id]++; 

            // 1. Assign the operator first
            $availableOperators = array_filter($operatorPool, function($op) {
                return $op['franchise_count'] < 7;
            });
            $availableOperators = array_values($availableOperators);
            $randomIndex = array_rand($availableOperators);
            $chosenOperatorData = &$availableOperators[$randomIndex];
            
            foreach ($operatorPool as &$op) {
                if ($op['model']->id === $chosenOperatorData['model']->id) {
                    $op['franchise_count']++;
                    break;
                }
            }

            // 2. Generate a Franchise Date STRICTLY AFTER the chosen operator registered
            $minFranchiseTimestamp = $chosenOperatorData['model']->created_at->timestamp;
            $maxFranchiseTimestamp = Carbon::now()->timestamp;
            $randomDate = Carbon::createFromTimestamp(rand($minFranchiseTimestamp, $maxFranchiseTimestamp));

            // Photo downloads
            $photoId = $i * 10;
            $unitPaths = [];
            $angles = ['front', 'back', 'left', 'right'];

            foreach ($angles as $index => $angle) {
                $path = "units/unit_{$i}_{$angle}.jpg";
                if (!Storage::disk('public')->exists($path)) {
                    try {
                        $keyword = in_array($angle, ['left', 'right']) ? 'motorcycle,sidecar' : "motorcycle,{$angle}";
                        $imageData = file_get_contents("https://loremflickr.com/640/480/{$keyword}?lock=" . ($photoId + $index));
                        Storage::disk('public')->put($path, $imageData);
                    } catch (\Exception $e) {
                        $path = null;
                    }
                }
                $unitPaths[$angle] = $path;
            }

            $unit = Unit::create([
                'make_id' => $make->id,
                'plate_number' => strtoupper($faker->bothify('???-####')),
                'motor_number' => strtoupper($faker->bothify('MOT-########')),
                'chassis_number' => strtoupper($faker->bothify('CHS-########')),
                'model_year' => $faker->numberBetween(2015, 2026),
                'cr_number' => $faker->numerify('########'),
                'unit_front_photo' => $unitPaths['front'],
                'unit_back_photo'  => $unitPaths['back'],
                'unit_left_photo'  => $unitPaths['left'],
                'unit_right_photo' => $unitPaths['right'],
            ]);
            $unit->timestamps = false; $unit->created_at = $randomDate; $unit->updated_at = $randomDate; $unit->save();

            $franchise = Franchise::create([
                'franchise_number' => $franchiseNumber,
                'zone_id' => $zone->id,
                'date_issued' => $randomDate,
                'status' => 'Renewed',
            ]);
            $franchise->timestamps = false; $franchise->created_at = $randomDate; $franchise->updated_at = $randomDate; $franchise->save();

            $activeUnit = ActiveUnit::create([
                'franchise_id' => $franchise->id,
                'new_unit_id' => $unit->id,
                'date_changed' => $franchise->date_issued,
                'remarks' => 'Initial Unit Assignment',
            ]);
            $activeUnit->timestamps = false; $activeUnit->created_at = $randomDate; $activeUnit->updated_at = $randomDate; $activeUnit->save();

            $ownership = Ownership::create([
                'franchise_id' => $franchise->id,
                'new_operator_id' => $chosenOperatorData['model']->id,
                'date_transferred' => $franchise->date_issued,
            ]);
            $ownership->timestamps = false; $ownership->created_at = $randomDate; $ownership->updated_at = $randomDate; $ownership->save();

            try {
                $qrContent = route('franchises.public_show', $franchise->id);
            } catch (\Exception $e) {
                $qrContent = url('/franchise/public/' . $franchise->id); 
            }
            
            $qrImage = QrCode::format('svg')->size(300)->generate($qrContent);
            $filename = 'qr-' . $franchise->id . '.svg';
            Storage::disk('public')->put('qrcodes/' . $filename, $qrImage);

            $franchise->update([
                'active_unit_id' => $activeUnit->id,
                'ownership_id' => $ownership->id,
                'qr_code' => $filename,
            ]);
        }

        $this->command->info('Successfully seeded 50 flawlessly timed Franchises!');
    }
}