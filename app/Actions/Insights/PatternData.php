<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Http\Requests\Insights\StorePatternRequest;
use App\Http\Requests\Insights\UpdatePatternRequest;

final readonly class PatternData
{
    public function __construct(
        public string $title,
        public ?string $description,
    ) {}

    public static function fromRequest(StorePatternRequest|UpdatePatternRequest $request): self
    {
        return new self(
            title:       $request->validated('title'),
            description: $request->validated('description') ?: null,
        );
    }
}
