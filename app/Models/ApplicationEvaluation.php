<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'requirement_id',
        'file_path',
        'is_compliant',
        'remarks'
    ];

    // [!code focus] This was missing
    public function requirement()
    {
        return $this->belongsTo(EvaluationRequirement::class, 'requirement_id');
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}