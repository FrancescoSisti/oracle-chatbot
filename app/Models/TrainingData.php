<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingData extends Model
{
    protected $fillable = [
        'text',
        'category',
        'is_verified',
        'confidence_score'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'confidence_score' => 'float'
    ];
}
