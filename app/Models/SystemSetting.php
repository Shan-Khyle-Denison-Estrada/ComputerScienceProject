<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'allow_new_applications' => 'boolean',
        'surcharge_rate' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'ordinances' => 'array',
        'faqs' => 'array',
    ];
}