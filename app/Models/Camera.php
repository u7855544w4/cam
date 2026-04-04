<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Camera extends Model
{
    protected $fillable = [
        'nvr_id',
        'name',
        'ip',
        'channel',
        'rtsp_url',
        'location',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'channel' => 'integer',
    ];

    public function nvr(): BelongsTo
    {
        return $this->belongsTo(Nvr::class);
    }

    public function detections(): HasMany
    {
        return $this->hasMany(Detection::class);
    }

    public function latestDetections()
    {
        return $this->hasMany(Detection::class)->latest('detected_at')->limit(10);
    }

    public function getRtspUrlAttribute(): string
    {
        // If NVR exists, generate RTSP URL from NVR settings
        if ($this->nvr && $this->channel) {
            $protocol = 'rtsp';
            return "{$protocol}://{$this->nvr->ip}:{$this->nvr->port}/channel/{$this->channel}/stream/0";
        }
        
        // Otherwise return custom RTSP URL
        return $this->attributes['rtsp_url'] ?? '';
    }
}