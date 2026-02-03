<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Franchise extends Model
{
    use HasFactory;

    protected $fillable = [
        'ownership_id', 'active_unit_id', 'driver_id', 'zone_id', 
        'date_issued', 'status', 'qr_code'
    ];

    // The CURRENT active ownership record
    public function currentOwnership()
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    // The CURRENT active unit record
    public function currentActiveUnit()
    {
        return $this->belongsTo(ActiveUnit::class, 'active_unit_id');
    }

    public function driver() { return $this->belongsTo(Driver::class); }
    public function zone() { return $this->belongsTo(Zone::class); }
    
    // History relations
    public function ownershipHistory() { return $this->hasMany(Ownership::class)->latest(); }
    public function unitHistory() { return $this->hasMany(ActiveUnit::class)->latest(); }
}