<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'contact_number',
        'street',
        'barangay',
        'city',
        'license_number',
        'license_expiration_date',
        'user_photo',
        'license_front_photo',
        'license_back_photo',
        'status',
    ];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} " . ($this->middle_name ? "{$this->middle_name} " : "") . "{$this->last_name}";
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // NEW: Associative Entity Relationship
    public function driverAssignments()
    {
        return $this->hasMany(DriverAssignment::class);
    }

    // Helper to get Franchises directly
    public function franchises()
    {
        return $this->hasManyThrough(Franchise::class, DriverAssignment::class, 'driver_id', 'id', 'id', 'franchise_id');
    }
}