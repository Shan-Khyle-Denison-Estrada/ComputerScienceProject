<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposedUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'make_id', 'model_year', 
        'plate_number', 'motor_number', 'chassis_number', 'cr_number',
        'unit_front_photo', 'unit_back_photo', 'unit_left_photo', 'unit_right_photo',
        'cr_photo', 'or_photo', 'franchise_certificate_photo'
    ];

    public function application() { return $this->belongsTo(Application::class); }
    public function make() { return $this->belongsTo(UnitMake::class); }
    public function inspections() { return $this->hasMany(UnitInspection::class); }
}