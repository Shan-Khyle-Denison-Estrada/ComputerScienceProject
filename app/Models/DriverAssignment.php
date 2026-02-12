<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverAssignment extends Model
{
    use HasFactory;

    // Added is_active to fillable
    protected $fillable = ['franchise_id', 'driver_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function franchise()
    {
        return $this->belongsTo(Franchise::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}