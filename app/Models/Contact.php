<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    protected $fillable = ['name', 'birthdate', 'birth_year_unknown', 'death_date', 'relationship_type_id', 'photo', 'notes'];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'birth_year_unknown' => 'boolean',
            'death_date' => 'date',
        ];
    }

    public function isDeceased(): bool
    {
        return $this->death_date !== null;
    }

    public function relationshipType(): BelongsTo
    {
        return $this->belongsTo(RelationshipType::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function dates(): HasMany
    {
        return $this->hasMany(ContactDate::class)->orderBy('date');
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(ContactRelationship::class);
    }

    public function relatedRelationships(): HasMany
    {
        return $this->hasMany(ContactRelationship::class, 'related_contact_id');
    }

    public function age(): ?int
    {
        if ($this->birthdate === null || $this->birth_year_unknown) {
            return null;
        }

        $until = $this->death_date ?? now();

        return (int) $this->birthdate->diffInYears($until);
    }

    public function nextBirthday(): ?Carbon
    {
        if ($this->birthdate === null || $this->isDeceased()) {
            return null;
        }

        $next = $this->birthdate->copy()->year(now()->year);

        if ($next->isPast() && ! $next->isToday()) {
            $next->addYear();
        }

        return $next;
    }

    public function daysUntilBirthday(): ?int
    {
        $next = $this->nextBirthday();

        return $next === null ? null : (int) now()->startOfDay()->diffInDays($next);
    }
}
