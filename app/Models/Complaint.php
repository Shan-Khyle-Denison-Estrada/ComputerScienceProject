<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'franchise_id',
        'nature_of_complaint',
        'remarks',
        'status', // Added status to fillable
        'fare_collected',
        'pick_up_point',
        'drop_off_point',
        'complainant_contact',
        'incident_date',
        'incident_time'
    ];

    public function franchise()
    {
        return $this->belongsTo(Franchise::class);
    }
}