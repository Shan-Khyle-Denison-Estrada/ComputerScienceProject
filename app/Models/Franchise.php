<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Franchise extends Model
{
    use HasFactory;

    protected $fillable = [
        'ownership_id', 'active_unit_id', 'driver_id', 'zone_id', 
        'date_issued', 'qr_code' 
        // We removed 'status' from fillable if we are strictly using the calculated one, 
        // but you can keep it if you want to store manual overrides.
    ];

    protected $appends = ['status']; // Ensure this is sent to Vue

    // --- Relationships ---
    public function currentOwnership() { return $this->belongsTo(Ownership::class, 'ownership_id'); }
    public function currentActiveUnit() { return $this->belongsTo(ActiveUnit::class, 'active_unit_id'); }
    public function driver() { return $this->belongsTo(Driver::class); }
    public function zone() { return $this->belongsTo(Zone::class); }
    public function ownershipHistory() { return $this->hasMany(Ownership::class)->latest(); }
    public function unitHistory() { return $this->hasMany(ActiveUnit::class)->latest(); }
    
    // Add relation to Assessments
    public function assessments() { return $this->hasMany(Assessment::class); }

    // --- Dynamic Status Logic ---
    public function getStatusAttribute()
    {
        // 1. Check for Termination (No payment in 3 years)
        // We need to check the latest payment across all assessments
        $lastPaymentDate = $this->assessments->flatMap(function ($assessment) {
            return $assessment->payments;
        })->sortByDesc('created_at')->first()?->created_at;

        // If no payment ever, use date_issued
        $referenceDate = $lastPaymentDate ? Carbon::parse($lastPaymentDate) : Carbon::parse($this->date_issued);

        if ($referenceDate->diffInYears(now()) >= 3) {
            return 'terminated';
        }

        // 2. Check for Pending Renewal (Pending or Overdue assessments exist)
        $hasPendingAssessments = $this->assessments->whereIn('assessment_status', ['pending', 'overdue'])->isNotEmpty();

        if ($hasPendingAssessments) {
            return 'pending renewal';
        }

        // 3. Default to Renewed
        return 'renewed';
    }
}