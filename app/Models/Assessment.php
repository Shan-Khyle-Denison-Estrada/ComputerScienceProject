<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'franchise_id',
        'assessment_date',
        'assessment_due',
        'total_amount_due',
        'assessment_status',
        'remarks',
        'date_approved'
    ];

    // 1. Add 'balance' and 'total_paid' here so they are sent to Vue
    protected $appends = ['balance', 'total_paid'];

    public function franchise()
    {
        return $this->belongsTo(Franchise::class);
    }

    public function particulars()
    {
        return $this->belongsToMany(Particular::class, 'assessment_particulars')
                    ->withPivot('quantity', 'subtotal')
                    ->withTimestamps();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // 2. This is the method that was missing/undefined
    public function getTotalPaidAttribute()
    {
        return $this->payments->sum('amount_paid');
    }

    // 3. This calculates the balance
    public function getBalanceAttribute()
    {
        // Ensure we treat these as numbers
        return (float)$this->total_amount_due - (float)$this->total_paid;
    }
}