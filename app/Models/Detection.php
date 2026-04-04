<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Detection extends Model
{
    protected $fillable = [
        'person_id',
        'camera_id',
        'detected_at',
        'snapshot',
        'confidence',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'confidence' => 'float',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }
}