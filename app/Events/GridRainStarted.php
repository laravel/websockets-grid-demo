<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GridRainStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $emoji)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('grid');
    }

    public function broadcastAs(): string
    {
        return 'rain-started';
    }

    public function broadcastWith(): array
    {
        return ['emoji' => $this->emoji];
    }
}
