<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RelationshipTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RelationshipType extends Model
{
    /** @use HasFactory<RelationshipTypeFactory> */
    use HasFactory;

    protected $fillable = ['name', 'sort_order'];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
