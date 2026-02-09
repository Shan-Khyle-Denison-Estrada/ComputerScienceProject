<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveUnit extends Model
{
    use HasFactory;

    protected $fillable = ['franchise_id', 'new_unit_id', 'previous_unit_id', 'date_changed', 'remarks'];

    public function franchise() { return $this->belongsTo(Franchise::class); }
    public function previousUnit() { return $this->belongsTo(Unit::class, 'previous_unit_id'); }
    public function newUnit() { return $this->belongsTo(Unit::class, 'new_unit_id'); }
    public function activeUnitHistory()
{
    // Links to the history log of this unit being used
    return $this->hasMany(ActiveUnit::class, 'new_unit_id');
}
}