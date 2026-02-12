<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationRequirement extends Model
{
    protected $fillable = ['application_type', 'name', 'is_required'];
}