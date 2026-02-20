<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'assessment_date',
        'assessment_due',
        'total_amount_due',
        'assessment_status',
        'remarks',
        'date_approved'
    ];

public function application()
    {
        return $this->belongsTo(Application::class); // <-- Changed from Franchise
    }

    public function particulars()
    {
        return $this->belongsToMany(Particular::class, 'assessment_particulars')
                    ->withPivot('quantity', 'subtotal')
                    ->withTimestamps();
    }

    // NEW: Relationship to Payments
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Helper to calculate balance
    public function getBalanceAttribute()
    {
        return $this->total_amount_due - $this->payments->sum('amount_paid');
    }
}