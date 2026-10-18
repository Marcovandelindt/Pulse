<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Carbon $played_at
 * @property Track $track
 */
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
