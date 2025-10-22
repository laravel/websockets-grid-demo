<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserCountUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $count)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('grid-active-users');
    }

    public function broadcastAs(): string
    {
        return 'user-count-updated';
    }

    public function broadcastWith(): array
    {
        return ['count' => $this->count];
    }
}
