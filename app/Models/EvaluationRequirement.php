<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EvaluationRequirement extends Model {
    protected $fillable = ['name', 'group', 'is_active'];
}