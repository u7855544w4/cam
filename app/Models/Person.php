<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    protected $fillable = [
        'name',
        'type',
        'image',
        'face_encoding',
    ];

    protected $casts = [
        'face_encoding' => 'array',
    ];

    public function detections(): HasMany
    {
        return $this->hasMany(Detection::class);
    }

    public function latestDetection()
    {
        return $this->hasOne(Detection::class)->latestOf('detected_at');
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'employee' => 'موظف',
            'visitor' => 'زائر',
            'citizen' => 'مواطن',
            default => $this->type,
        };
    }
}