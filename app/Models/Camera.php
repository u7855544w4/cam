<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Camera extends Model
{
    protected $fillable = [
        'name',
        'ip',
        'rtsp_url',
        'location',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function detections(): HasMany
    {
        return $this->hasMany(Detection::class);
    }

    public function latestDetections()
    {
        return $this->hasMany(Detection::class)->latest('detected_at')->limit(10);
    }
}