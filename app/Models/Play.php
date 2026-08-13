<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Play extends Model
{
    protected $fillable = ['track_id', 'played_at', 'source', 'context'];

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
            'context' => 'array',
        ];
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class)->with(['album', 'artists']);
    }
}
