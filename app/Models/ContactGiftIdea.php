<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactGiftIdea extends Model
{
    protected $fillable = ['contact_id', 'idea', 'notes'];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
