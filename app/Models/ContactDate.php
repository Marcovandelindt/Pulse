<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ContactDate extends Model
{
    protected $fillable = ['contact_id', 'label', 'date'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
