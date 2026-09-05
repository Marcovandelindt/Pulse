<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    protected $fillable = ['ai_conversation_id', 'role', 'content'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class);
    }
}
