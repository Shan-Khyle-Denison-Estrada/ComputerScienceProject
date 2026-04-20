<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Assessment;
use App\Models\Payment;
use App\Models\Particular;
use App\Models\Franchise;

class PaymentSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('payments')->truncate();
        DB::table('assessment_particulars')->truncate();
        DB::table('assessments')->truncate();
        Schema::enableForeignKeyConstraints();

        $faker = \Faker\Factory::create('en_PH');
        $franchises = Franchise::with('currentOwnership.newOwner.user')->get();
        $particulars = Particular::where('amount', '>', 0)->get();

        if ($franchises->isEmpty() || $particulars->isEmpty()) {
            $this->command->error('Please run GraduationSeeder and ParticularSeeder first!');
            return;
        }

        $timeline = [];
        $nowTimestamp = Carbon::now()->timestamp;

        // Loop through each of the 50 franchises and give them exactly 10 assessments each (50 x 10 = 500)
        foreach ($franchises as $franchise) {
            $franchiseTimestamp = Carbon::parse($franchise->date_issued)->timestamp;
            
            for ($i = 0; $i < 10; $i++) {
                // Ensure the payment/assessment date is strictly AFTER the franchise was created
                $randomTimestamp = rand($franchiseTimestamp, $nowTimestamp);
                
                $timeline[] = [
                    'franchise' => $franchise,
                    'date' => Carbon::createFromTimestamp($randomTimestamp),
                    'is_paid' => (rand(1, 100) <= 80) // 80% chance of being paid
                ];
            }
        }

        // Now that we have all 500 assessments generated, we sort the entire array chronologically
        // This guarantees that OR numbers will perfectly increment over the course of 2 years
        usort($timeline, function($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        $this->command->info('Seeding 500 chronological Assessments tied flawlessly to Franchise dates...');
        
        $paymentSequence = 1;

        foreach ($timeline as $event) {
            $date = $event['date'];
            $dueDate = $date->copy()->addDays(30);
            $franchise = $event['franchise'];
            $ownerUser = $franchise->currentOwnership->newOwner->user ?? null;

            $selectedParticulars = $particulars->random(rand(1, 3));
            $totalAmount = 0;
            $attachData = [];

            foreach ($selectedParticulars as $p) {
                $qty = 1;
                $subtotal = $p->amount * $qty;
                $totalAmount += $subtotal;
                $attachData[$p->id] = ['quantity' => $qty, 'subtotal' => $subtotal];
            }

            $assessment = Assessment::create([
                'franchise_id' => $franchise->id,
                'assessment_date' => $date->format('Y-m-d'),
                'assessment_due' => $dueDate->format('Y-m-d'),
                'total_amount_due' => $totalAmount,
                'assessment_status' => 'pending',
                'remarks' => 'Auto-generated assessment for presentation purposes.',
            ]);
            $assessment->timestamps = false; $assessment->created_at = $date; $assessment->updated_at = $date; $assessment->save();

            $assessment->particulars()->attach($attachData);

            if ($event['is_paid']) {
                $sequenceStr = str_pad($paymentSequence, 4, '0', STR_PAD_LEFT);
                $orNumber = 'OR-' . $date->format('Ymd') . '-' . $sequenceStr;
                $paymentSequence++;

                $payment = Payment::create([
                    'assessment_id' => $assessment->id,
                    'or_number' => $orNumber,
                    'amount_paid' => $totalAmount,
                    'payee_first_name' => $ownerUser ? $ownerUser->first_name : $faker->firstName,
                    'payee_middle_name' => $ownerUser ? $ownerUser->middle_name : $faker->lastName,
                    'payee_last_name' => $ownerUser ? $ownerUser->last_name : $faker->lastName,
                    'payee_contact_number' => $ownerUser ? $ownerUser->contact_number : '09' . $faker->numerify('#########'),
                    'payee_street_address' => $ownerUser ? $ownerUser->street_address : $faker->streetName,
                    'payee_province' => 'Zamboanga del Sur',
                    'payee_city' => 'Zamboanga City',
                    'payee_barangay' => $ownerUser ? $ownerUser->barangay : 'Tetuan',
                ]);
                $payment->timestamps = false; $payment->created_at = $date; $payment->updated_at = $date; $payment->save();

                $assessment->update(['assessment_status' => 'paid']);
            } else {
                if ($dueDate->isPast()) {
                    $assessment->update(['assessment_status' => 'overdue']);
                }
            }
        }

        $this->command->info('Successfully seeded 500 chronologically sequenced Payments!');
    }
}