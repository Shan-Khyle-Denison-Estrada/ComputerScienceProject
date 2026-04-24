<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'force_password_change',
        'user_photo',
        'signature_photo',
        'contact_number',
        'street_address',
        'barangay',
        'city',
        'province',
        'role',
        'status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Automatically append active_roles so Vue/Inertia can see it immediately
    protected $appends = ['active_roles'];

    // Relationship to active temporary roles
    public function temporaryRoles()
    {
        return $this->hasMany(TemporaryRole::class)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                    });
    }

    // Accessor that merges the base role with temporary roles into a single array
    public function getActiveRolesAttribute()
    {
        $baseRole = $this->role instanceof \BackedEnum ? $this->role->value : $this->role;
        $roles = [$baseRole];
        
        if ($this->relationLoaded('temporaryRoles') || $this->exists) {
            $tempRoles = $this->temporaryRoles()->pluck('role')
                ->map(fn($r) => $r instanceof \BackedEnum ? $r->value : $r)
                ->toArray();
            $roles = array_merge($roles, $tempRoles);
        }
        
        return array_unique($roles);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
    
    // Helper to get full name easily for display
    public function getFullNameAttribute()
    {
        return "{$this->first_name} " . ($this->middle_name ? "{$this->middle_name} " : "") . "{$this->last_name}";
    }

    // Relationship to Operator profile (if role is franchise_owner)
    public function operator()
    {
        return $this->hasOne(Operator::class);
    }

    // --- ADD THIS NEW METHOD ---
    // Accessor that fetches all permissions based on the user's active roles
    public function getPermissionsAttribute(): array
    {
        $permissions = [];
        $permissionsMap = config('permissions', []);

        // 1. Get Base Role permissions from config
        $baseRole = $this->role instanceof \BackedEnum ? $this->role->value : $this->role;
        if (isset($permissionsMap[$baseRole])) {
            $permissions = array_merge($permissions, $permissionsMap[$baseRole]);
        }

        // 2. Get Temporary Role permissions from the Database JSON column
        if ($this->relationLoaded('temporaryRoles') || $this->exists) {
            foreach ($this->temporaryRoles as $tempRole) {
                if (!empty($tempRole->permissions)) {
                    $permissions = array_merge($permissions, $tempRole->permissions);
                }
            }
        }
        
        return array_values(array_unique($permissions));
    }
}