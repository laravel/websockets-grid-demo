<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GridCellUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $data)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('grid');
    }

    public function broadcastAs(): string
    {
        return 'cell-updated';
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }
}
