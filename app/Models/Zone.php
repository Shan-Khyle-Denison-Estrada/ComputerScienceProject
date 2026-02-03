<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'color',
        'coverage',
    ];

    protected $casts = [
        'coverage' => 'array', // Automatically cast JSON to PHP Array
    ];
}