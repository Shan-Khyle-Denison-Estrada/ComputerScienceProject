<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'tin_number'];

    // Link back to the User (for name, email, address, etc.)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // History where this operator became the owner
    public function acquiredOwnerships()
    {
        return $this->hasMany(Ownership::class, 'new_operator_id');
    }
}