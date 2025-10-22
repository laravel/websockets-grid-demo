<?php

namespace App\Broadcasting;

class GridPresence
{
    public function join(): array|bool
    {
        return [
            'id' => \Str::uuid(),
        ];
    }
}
