<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nvr extends Model
{
    protected $fillable = [
        'name',
        'ip',
        'port',
        'username',
        'password',
        'model',
        'manufacturer',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port' => 'integer',
    ];

    public function cameras(): HasMany
    {
        return $this->hasMany(Camera::class);
    }

    public function getChannelsCountAttribute(): int
    {
        return $this->cameras()->count();
    }

    public function getFullUrlAttribute(): string
    {
        return "rtsp://{$this->ip}:{$this->port}";
    }
}