<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GridCellClicked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $position,
        public int $clickCount,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('grid'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'cell-clicked';
    }
}
