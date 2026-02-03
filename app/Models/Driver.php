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

    // Helper for full name
    public function getFullNameAttribute()
    {
        return "{$this->first_name} " . ($this->middle_name ? "{$this->middle_name} " : "") . "{$this->last_name}";
    }

    // Relationship to User (Franchise Owner)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}