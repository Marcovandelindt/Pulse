<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpotifyImport extends Model
{
    protected $fillable = [
        'filename',
        'file_path',
        'status',
        'total_entries',
        'processed',
        'synced',
        'skipped',
        'error',
    ];

    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_entries === 0) {
            return 0;
        }

        return min(100, (int) round(($this->processed / $this->total_entries) * 100));
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'processing'], true);
    }
}
