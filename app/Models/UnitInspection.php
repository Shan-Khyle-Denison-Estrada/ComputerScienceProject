<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposed_unit_id',
        'inspection_item_id',
        'rating',
        'remarks',
        'unit_id',           // NEW
        'application_id'     // NEW
    ];

    public function proposedUnit()
    {
        return $this->belongsTo(ProposedUnit::class);
    }

    // New Relationships
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function inspectionItem()
    {
        return $this->belongsTo(InspectionItem::class);
    }
}