<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SleepModeTrack extends Model
{
    protected $fillable = [
        'title',
        'game_name',
        'path',
        'sort_order',
    ];

    public function displayTitle(): string
    {
        if ($this->title) {
            return $this->title;
        }

        return pathinfo($this->path, PATHINFO_FILENAME);
    }
}
