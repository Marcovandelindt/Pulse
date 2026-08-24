<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventType;
use App\Enums\RecurrenceType;
use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CalendarEvent extends Model
{
    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'ends_at',
        'all_day',
        'type',
        'color',
        'recurrence',
        'recurrence_ends_at',
        'contact_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'          => 'datetime',
            'ends_at'            => 'datetime',
            'all_day'            => 'boolean',
            'type'               => EventType::class,
            'recurrence'         => RecurrenceType::class,
            'recurrence_ends_at' => 'date',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function effectiveColor(): string
    {
        return $this->color ?? $this->type->color();
    }
}
