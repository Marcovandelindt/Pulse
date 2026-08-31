<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ChangelogEntry extends Model
{
    protected $fillable = [
        'commit_hash',
        'type',
        'scope',
        'title',
        'description',
        'files_changed',
        'stats',
        'committed_at',
    ];

    protected function casts(): array
    {
        return [
            'files_changed' => 'array',
            'stats' => 'array',
            'committed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function typeColor(): string
    {
        return match ($this->type) {
            'feat' => 'feat',
            'fix' => 'fix',
            'refactor' => 'refactor',
            'style' => 'style',
            'docs' => 'docs',
            'test' => 'test',
            'perf' => 'perf',
            default => 'chore',
        };
    }
}
