<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ContactRelationship extends Model
{
    protected $fillable = ['contact_id', 'related_contact_id', 'type', 'date'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function relatedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'related_contact_id');
    }

    public function typeLabel(): string
    {
        return ucfirst($this->type);
    }

    /** @return list<string> */
    public static function types(): array
    {
        return ['married', 'partners', 'engaged', 'siblings', 'best friends'];
    }
}
