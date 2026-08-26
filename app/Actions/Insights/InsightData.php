<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Http\Requests\Insights\StoreInsightRequest;
use App\Http\Requests\Insights\UpdateInsightRequest;

final readonly class InsightData
{
    public function __construct(
        public string $title,
        public string $content,
        public ?string $summary,
        public ?string $category,
        public ?array $tags,
        public bool $isPinned,
        public bool $isQuickRef,
    ) {}

    public static function fromRequest(StoreInsightRequest|UpdateInsightRequest $request): self
    {
        $rawTags = $request->validated('tags');
        $tags    = $rawTags
            ? array_values(array_filter(array_map('trim', explode(',', $rawTags))))
            : null;

        return new self(
            title:      $request->validated('title'),
            content:    $request->validated('content'),
            summary:    $request->validated('summary') ?: null,
            category:   $request->validated('category') ?: null,
            tags:       $tags ?: null,
            isPinned:   (bool) ($request->validated()['is_pinned'] ?? false),
            isQuickRef: (bool) ($request->validated()['is_quick_ref'] ?? false),
        );
    }
}
