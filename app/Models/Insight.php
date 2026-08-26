<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Insight extends Model
{
    protected $fillable = [
        'title',
        'content',
        'summary',
        'category',
        'tags',
        'is_pinned',
        'is_quick_ref',
    ];

    protected function casts(): array
    {
        return [
            'tags'         => 'array',
            'is_pinned'    => 'boolean',
            'is_quick_ref' => 'boolean',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
        ];
    }

    public function patterns(): BelongsToMany
    {
        return $this->belongsToMany(Pattern::class);
    }

    public function related(): BelongsToMany
    {
        return $this->belongsToMany(Insight::class, 'insight_insight', 'insight_id', 'related_insight_id');
    }

    public function relatedBy(): BelongsToMany
    {
        return $this->belongsToMany(Insight::class, 'insight_insight', 'related_insight_id', 'insight_id');
    }

    /** @return Collection<int, Insight> */
    public function allRelated(): Collection
    {
        return $this->related->merge($this->relatedBy)->unique('id');
    }
}
