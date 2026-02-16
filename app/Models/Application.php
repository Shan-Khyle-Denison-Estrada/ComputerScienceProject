<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number', 'user_id', 'franchise_id', 'zone_id',
        'application_type', 'status', 'remarks',
        'first_name', 'middle_name', 'last_name', 'contact_number', 'email', 'tin_number',
        'street_address', 'barangay', 'city', 'submitted_at', 'reviewed_at'
    ];

    // Relationships
    public function user() { return $this->belongsTo(User::class); }
    public function franchise() { return $this->belongsTo(Franchise::class); }
    public function zone() { return $this->belongsTo(Zone::class); }
    public function proposedUnits() { return $this->hasMany(ProposedUnit::class); }
    public function evaluations() { return $this->hasMany(ApplicationEvaluation::class); }

    // Helper for Full Name
    public function getApplicantNameAttribute()
    {
        return "{$this->first_name} {$this->middle_name} {$this->last_name}";
    }
}