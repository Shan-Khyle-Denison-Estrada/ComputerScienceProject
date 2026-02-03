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

    public function particulars()
    {
        return $this->belongsToMany(Particular::class, 'assessment_particulars')
                    ->withPivot('quantity', 'subtotal')
                    ->withTimestamps();
    }
}