<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationAttachment extends Model
{
    protected $fillable = ['application_id', 'application_requirement_id', 'file_path'];

    public function requirement() {
        return $this->belongsTo(ApplicationRequirement::class, 'application_requirement_id');
    }
}