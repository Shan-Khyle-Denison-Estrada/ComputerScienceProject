<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'previous_unit_id',
        'new_unit_id',
        'date_changed',
        'remarks'
    ];

    public function previousUnit() { return $this->belongsTo(Unit::class, 'previous_unit_id'); }
    public function newUnit() { return $this->belongsTo(Unit::class, 'new_unit_id'); }
}