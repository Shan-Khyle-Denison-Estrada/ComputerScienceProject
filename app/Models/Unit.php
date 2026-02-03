<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'make_id',
        'plate_number',
        'motor_number',
        'chassis_number',
        'model_year',
        'cr_number',
        'unit_front_photo',
        'unit_back_photo',
        'unit_left_photo',
        'unit_right_photo',
    ];

    public function make()
    {
        return $this->belongsTo(UnitMake::class, 'make_id');
    }
}