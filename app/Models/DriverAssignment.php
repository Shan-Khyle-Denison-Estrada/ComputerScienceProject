<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['franchise_id', 'driver_id', 'is_active', 'schedule'];

    protected $casts = [
        'is_active' => 'boolean',
        'schedule' => 'array', // Cast JSON to array automatically
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