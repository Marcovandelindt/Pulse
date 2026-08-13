<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AlbumListen extends Model
{
    protected $fillable = [
        'album_id',
        'listened_at',
        'year_only',
        'notes',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'listened_at' => 'datetime',
            'year_only' => 'boolean',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function formattedListenedAt(): string
    {
        if ($this->listened_at === null) {
            return 'Date unknown';
        }

        if ($this->year_only) {
            return $this->listened_at->format('Y');
        }

        return $this->listened_at->format('d M Y');
    }
}
