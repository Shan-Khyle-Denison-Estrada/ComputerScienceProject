<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'or_number',
        'amount_paid',
        'payee_first_name',
        'payee_middle_name',
        'payee_last_name',
        'payee_contact_number',
        'payee_street_address',
        'payee_province', // <-- ADDED THIS LINE
        'payee_barangay',
        'payee_city',
    ];

    public function scopeFilter($query, array $filters)
    {
        // Search by Payee Name, Contact Number, or OR Number
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('payee_first_name', 'like', '%'.$search.'%')
                  ->orWhere('payee_last_name', 'like', '%'.$search.'%')
                  ->orWhere('payee_contact_number', 'like', '%'.$search.'%')
                  ->orWhere('or_number', 'like', '%'.$search.'%'); 
            });
        });

        // Filter by City
        $query->when($filters['city'] ?? null, function ($query, $city) {
            $query->where('payee_city', 'like', '%'.$city.'%');
        });
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
}