<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Mood;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'title',
        'content',
        'mood',
        'energy_level',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'mood'         => Mood::class,
            'tags'         => 'array',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
        ];
    }
}
