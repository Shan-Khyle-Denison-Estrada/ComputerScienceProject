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
    ];

    public function proposedUnit()
    {
        return $this->belongsTo(ProposedUnit::class);
    }

    public function inspectionItem()
    {
        return $this->belongsTo(InspectionItem::class);
    }
}