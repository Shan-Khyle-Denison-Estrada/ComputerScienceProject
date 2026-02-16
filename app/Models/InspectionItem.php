<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class InspectionItem extends Model {
    protected $fillable = ['name', 'rating_options'];
    protected $casts = ['rating_options' => 'array'];
}