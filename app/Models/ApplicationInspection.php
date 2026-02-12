<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationInspection extends Model
{
    protected $fillable = ['application_id', 'application_requirement_id', 'remarks', 'score'];

    public function requirement() {
        return $this->belongsTo(ApplicationRequirement::class, 'application_requirement_id');
    }
}