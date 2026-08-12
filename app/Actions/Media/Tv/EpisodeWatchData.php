<?php

declare(strict_types=1);

namespace App\Actions\Media\Tv;

use App\Http\Requests\Media\MarkEpisodeWatchedRequest;
use Carbon\Carbon;

final readonly class EpisodeWatchData
{
    public function __construct(
        public ?Carbon $watchedAt,
        public bool $yearOnly,
        public ?string $notes,
        public ?int $rating,
    ) {}

    public static function fromRequest(MarkEpisodeWatchedRequest $request): self
    {
        return new self(
            watchedAt: $request->validated('watched_at')
                ? Carbon::parse($request->validated('watched_at'))
                : null,
            yearOnly: (bool) $request->validated('year_only', false),
            notes: $request->validated('notes'),
            rating: $request->validated('rating') !== null ? (int) $request->validated('rating') : null,
        );
    }
}
