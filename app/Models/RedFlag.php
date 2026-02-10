<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedFlag extends Model
{
    protected $fillable = ['franchise_id', 'nature_id', 'remarks', 'status'];

    public function franchise() {
        return $this->belongsTo(Franchise::class);
    }

    public function nature() {
        return $this->belongsTo(NatureOfRedFlag::class, 'nature_id');
    }
}