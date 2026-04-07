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
        // If custom RTSP URL is provided, use it
        if (!empty($this->attributes['rtsp_url'])) {
            return $this->attributes['rtsp_url'];
        }
        
        // If NVR exists, generate RTSP URL from NVR settings
        if ($this->nvr && $this->channel) {
            $protocol = 'rtsp';
            return "{$protocol}://{$this->nvr->ip}:{$this->nvr->port}/channel/{$this->channel}/stream/0";
        }
        
        // For direct IP cameras (no NVR)
        if ($this->ip) {
            $port = 554;
            
            // UNV (UniNVR) cameras RTSP format
            // Format: rtsp://<ip>:554/media/video1 or media/video2
            $stream = $this->channel == 2 ? 'media/video2' : 'media/video1';
            return "rtsp://{$this->ip}:{$port}/{$stream}";
        }
        
        return '';
    }
}