<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'franchise_id', 'assessment_id', 
        'type', 'status', 'remarks', 'proposed_data'
    ];

    protected $casts = [
        'proposed_data' => 'array', // automatically decode JSON
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function franchise() { return $this->belongsTo(Franchise::class); }
    public function assessment() { return $this->belongsTo(Assessment::class); }
    public function attachments() { return $this->hasMany(ApplicationAttachment::class); }
    
    // Helper to get requirements for this specific type
    public function requirements() {
        return ApplicationRequirement::where('application_type', $this->type)->get();
    }

    public function inspections() {
        return $this->hasMany(ApplicationInspection::class);
    }
}