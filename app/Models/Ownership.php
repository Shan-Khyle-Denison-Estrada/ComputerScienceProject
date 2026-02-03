<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ownership extends Model
{
    use HasFactory;

    protected $fillable = [
        'new_operator_id',
        'previous_operator_id',
        'date_transferred'
    ];

    public function newOwner()
    {
        return $this->belongsTo(Operator::class, 'new_operator_id');
    }

    public function previousOwner()
    {
        return $this->belongsTo(Operator::class, 'previous_operator_id');
    }
}