<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'assessment_date',
        'assessment_due',
        'total_amount_due',
        'assessment_status',
        'remarks',
        'date_approved'
    ];

    public function application()
    {
        return $this->belongsTo(Application::class); // <-- Changed from Franchise
    }

    public function particulars()
    {
        return $this->belongsToMany(Particular::class, 'assessment_particulars')
                    ->withPivot('quantity', 'subtotal')
                    ->withTimestamps();
    }

    // NEW: Relationship to Payments
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Helper to calculate balance
    public function getBalanceAttribute()
    {
        return $this->total_amount_due - $this->payments->sum('amount_paid');
    }

    public function recalculatePenalties()
    {
        // 1. We only recalculate unpaid assessments
        if (!in_array($this->assessment_status, ['pending', 'overdue'])) {
            return;
        }

        $now = now();
        $dueDate = Carbon::parse($this->assessment_due);
        $this->loadMissing('particulars');

        $renewalBasis = 0;
        $baseTotal = 0;
        $syncData = [];

        // 2. Separate original base items from old penalties
        foreach ($this->particulars as $p) {
            if ($p->code !== 'surcharge' && $p->code !== 'interest') {
                $baseTotal += $p->pivot->subtotal;
                $syncData[$p->id] = [
                    'quantity' => $p->pivot->quantity,
                    'subtotal' => $p->pivot->subtotal
                ];

                if ($p->group === 'renewal') {
                    $renewalBasis += $p->pivot->subtotal;
                }
            }
        }

        // 3. If NOT late, or NO renewal items, just update status and exit
        if ($now->lte($dueDate) || $renewalBasis <= 0) {
            $this->particulars()->sync($syncData); // Removes old penalties if any existed
            $this->update([
                'total_amount_due' => $baseTotal,
                'assessment_status' => $now->gt($dueDate) ? 'overdue' : 'pending'
            ]);
            return;
        }

        // 4. PENALTY CALCULATIONS (Since it is Late)
        $settings = SystemSetting::first();
        $surchargeRate = ($settings->surcharge_rate ?? 25) / 100; 
        $interestRate = ($settings->interest_rate ?? 2) / 100;

        $monthsDelayed = (int) ceil($dueDate->floatDiffInMonths($now));
        if ($monthsDelayed === 0) $monthsDelayed = 1; 
        $yearsDelayed = (int) ceil($monthsDelayed / 12); 

        $surchargeP = Particular::where('code', 'surcharge')->first();
        $interestP = Particular::where('code', 'interest')->first();

        $penaltyTotal = 0;

        // Add Surcharge (Per Fiscal Year)
        if ($surchargeP) {
            $amount = $renewalBasis * $surchargeRate * $yearsDelayed;
            $syncData[$surchargeP->id] = ['quantity' => $yearsDelayed, 'subtotal' => $amount];
            $penaltyTotal += $amount;
        }

        // Add Interest (Per Month)
        if ($interestP) {
            $amount = $renewalBasis * $interestRate * $monthsDelayed;
            $syncData[$interestP->id] = ['quantity' => $monthsDelayed, 'subtotal' => $amount];
            $penaltyTotal += $amount;
        }

        // 5. Sync the new totals to the Database
        $this->particulars()->sync($syncData);
        $this->update([
            'total_amount_due' => $baseTotal + $penaltyTotal,
            'assessment_status' => 'overdue'
        ]);
    }
}