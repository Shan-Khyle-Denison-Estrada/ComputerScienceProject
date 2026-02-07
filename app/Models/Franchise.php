<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Franchise extends Model
{
    use HasFactory;

    protected $fillable = [
        'ownership_id', 'active_unit_id', 'zone_id', 
        'date_issued', 'qr_code'
        // 'driver_id' is no longer the source of truth, but kept if you haven't dropped the column yet
    ];

    protected $appends = ['status']; 

    // --- Relationships ---
    public function currentOwnership() { return $this->belongsTo(Ownership::class, 'ownership_id'); }
    public function currentActiveUnit() { return $this->belongsTo(ActiveUnit::class, 'active_unit_id'); }
    public function zone() { return $this->belongsTo(Zone::class); }
    public function ownershipHistory() { return $this->hasMany(Ownership::class)->latest(); }
    public function unitHistory() { return $this->hasMany(ActiveUnit::class)->latest(); }
    public function assessments() { return $this->hasMany(Assessment::class); }

    // NEW: Associative Entity Relationship
    public function driverAssignments() 
    { 
        return $this->hasMany(DriverAssignment::class); 
    }

    // Helper to get actual Driver models directly
    public function drivers()
    {
        return $this->hasManyThrough(Driver::class, DriverAssignment::class, 'franchise_id', 'id', 'id', 'driver_id');
    }

    // This fetches the single "current" driver (the latest one assigned)
    public function driver()
    {
        return $this->hasOneThrough(Driver::class, DriverAssignment::class, 'franchise_id', 'id', 'id', 'driver_id')
            ->latest('driver_assignments.created_at');
    }

    // --- Dynamic Status Logic ---
    public function getStatusAttribute()
    {
        // 1. Check for Termination (No payment in 3 years)
        $lastPaymentDate = $this->assessments->flatMap(function ($assessment) {
            return $assessment->payments;
        })->sortByDesc('created_at')->first()?->created_at;

        $referenceDate = $lastPaymentDate ? Carbon::parse($lastPaymentDate) : Carbon::parse($this->date_issued);

        if ($referenceDate->diffInYears(now()) >= 3) {
            return 'terminated';
        }

        // 2. Check for Pending Renewal
        $hasPendingAssessments = $this->assessments->whereIn('assessment_status', ['pending', 'overdue'])->isNotEmpty();

        if ($hasPendingAssessments) {
            return 'pending renewal';
        }

        // 3. Default to Renewed
        return 'renewed';
    }
}