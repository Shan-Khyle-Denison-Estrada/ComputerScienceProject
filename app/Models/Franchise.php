<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Franchise extends Model
{
    use HasFactory;

    // Added 'status' to fillable and removed the $appends array
    protected $fillable = [
        'ownership_id', 'active_unit_id', 'zone_id', 
        'date_issued', 'qr_code', 'status' 
    ];

    // --- Relationships ---
    public function currentOwnership() { return $this->belongsTo(Ownership::class, 'ownership_id'); }
    public function currentActiveUnit() { return $this->belongsTo(ActiveUnit::class, 'active_unit_id'); }
    public function zone() { return $this->belongsTo(Zone::class); }
    public function ownershipHistory() { return $this->hasMany(Ownership::class)->latest(); }
    public function unitHistory() { return $this->hasMany(ActiveUnit::class)->latest(); }
    
    public function assessments() 
    { 
        return $this->hasManyThrough(Assessment::class, Application::class); 
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

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

    public function driverLogs()
    {
        return $this->hasMany(DriverLog::class)->latest('started_at');
    }

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
    
    // NOTE: getStatusAttribute() has been completely removed.
}