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
    ];

    protected $appends = ['status']; 

    // --- Relationships ---
    public function currentOwnership() { return $this->belongsTo(Ownership::class, 'ownership_id'); }
    public function currentActiveUnit() { return $this->belongsTo(ActiveUnit::class, 'active_unit_id'); }
    public function zone() { return $this->belongsTo(Zone::class); }
    public function ownershipHistory() { return $this->hasMany(Ownership::class)->latest(); }
    public function unitHistory() { return $this->hasMany(ActiveUnit::class)->latest(); }
    
    // UPDATED: Now fetches assessments THROUGH the Application model
    public function assessments() 
    { 
        return $this->hasManyThrough(Assessment::class, Application::class); 
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    // Associative Entity Relationship
    public function driverAssignments() 
    { 
        return $this->hasMany(DriverAssignment::class); 
    }

    public function drivers()
    {
        return $this->hasManyThrough(Driver::class, DriverAssignment::class, 'franchise_id', 'id', 'id', 'driver_id');
    }

    public function driver()
    {
        return $this->hasOneThrough(Driver::class, DriverAssignment::class, 'franchise_id', 'id', 'id', 'driver_id')
            ->latest('driver_assignments.created_at');
    }

    // --- Accessors & Mutators ---
    public function getStatusAttribute()
    {
        $threeYearsAgo = Carbon::now()->subYears(3);

        // 1. Check for Termination (Unpaid Assessment > 3 Years)
        // We filter the assessments to find any that are NOT 'paid' 
        // AND were issued more than 3 years ago.
        $hasLongOverdueAssessment = $this->assessments->contains(function ($assessment) use ($threeYearsAgo) {
            // Check if status is pending or overdue (not paid)
            $isUnpaid = $assessment->assessment_status !== 'paid';
            
            // Check if the assessment date is older than 3 years
            $isOld = Carbon::parse($assessment->assessment_date)->lte($threeYearsAgo);

            return $isUnpaid && $isOld;
        });

        if ($hasLongOverdueAssessment) {
            return 'terminated';
        }

        // 2. Check for Pending Renewal
        // Check if there are any current pending/overdue assessments (regardless of age)
        $hasPendingAssessments = $this->assessments
            ->whereIn('assessment_status', ['pending', 'overdue'])
            ->isNotEmpty();

        if ($hasPendingAssessments) {
            return 'pending renewal';
        }

        // 3. Default to Renewed
        return 'renewed';
    }

    public function driverLogs()
    {
        return $this->hasMany(DriverLog::class)->latest('started_at');
    }

    // Helper to get the currently active assignment
    public function activeAssignment()
    {
        return $this->hasOne(DriverAssignment::class)->where('is_active', true);
    }
        public function complaints() 
    { 
        return $this->hasMany(Complaint::class)->latest(); 
    }
    
    public function redFlags() 
    { 
        return $this->hasMany(RedFlag::class)->latest(); 
    }
}