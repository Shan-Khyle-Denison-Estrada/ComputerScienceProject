<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FranchiseCertificateTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'content',
        'paper_size',
        'margins',
        'author_id'
    ];

    protected $casts = [
        'margins' => 'array',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}