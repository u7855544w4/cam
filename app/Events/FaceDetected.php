<?php

namespace App\Events;

use App\Models\Detection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FaceDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Detection $detection;

    public function __construct(Detection $detection)
    {
        $this->detection = $detection;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('detections'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->detection->id,
            'person' => [
                'id' => $this->detection->person->id,
                'name' => $this->detection->person->name,
                'type' => $this->detection->person->type,
                'image' => $this->detection->person->image,
            ],
            'camera' => [
                'id' => $this->detection->camera->id,
                'name' => $this->detection->camera->name,
                'location' => $this->detection->camera->location,
            ],
            'detected_at' => $this->detection->detected_at->toIso8601String(),
            'confidence' => $this->detection->confidence,
            'snapshot' => $this->detection->snapshot,
        ];
    }
}