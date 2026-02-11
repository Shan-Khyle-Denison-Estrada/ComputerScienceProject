<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Particular extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'code', 
        'description', 
        'amount', 
        'group', 
        'is_system'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'amount' => 'decimal:2'
    ];

    // Helper to get grouped particulars for the frontend
    public static function getGrouped()
    {
        return self::all()->groupBy(function($item) {
            return $item->group ?? 'optional';
        });
    }
}