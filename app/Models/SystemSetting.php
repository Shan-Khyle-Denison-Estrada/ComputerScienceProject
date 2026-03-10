<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'surcharge_rate' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'is_new_franchise_open' => 'boolean',
        'ordinances' => 'array',
        'faqs' => 'array',
    ];
}