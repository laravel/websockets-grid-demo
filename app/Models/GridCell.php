<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GridCell extends Model
{
    protected $fillable = ['position', 'emoji', 'click_count'];

    protected function casts(): array
    {
        return [
            'click_count' => 'integer',
        ];
    }
}
