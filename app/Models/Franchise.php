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

    public function getStatusAttribute()
    {
        // 1. Check for Pending Renewal Applications
        // A renewal application that is not fully 'Completed', 'Rejected', or 'Cancelled'
        if ($this->relationLoaded('applications')) {
            // Use collection filtering if eager loaded
            $hasPendingRenewal = $this->applications
                ->where('application_type', 'Renewal')
                ->whereNotIn('status', ['Completed', 'Rejected', 'Cancelled'])
                ->isNotEmpty();
        } else {
            // REMOVED 'clone' here. Just use the fresh query builder.
            $hasPendingRenewal = $this->applications()
                ->where('application_type', 'Renewal')
                ->whereNotIn('status', ['Completed', 'Rejected', 'Cancelled'])
                ->exists();
        }

        if ($hasPendingRenewal) {
            return 'pending renewal';
        }

        // 2. Check for Terminated Status
        // Terminate if more than 3 years have passed since the last date_issued
        if ($this->date_issued) {
            $threeYearsAgo = Carbon::now()->subYears(3);
            if (Carbon::parse($this->date_issued)->lte($threeYearsAgo)) {
                return 'terminated';
            }
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