<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryRole extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'role', 'permissions', 'granted_by', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'role' => UserRole::class,
            'permissions' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function granter()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}