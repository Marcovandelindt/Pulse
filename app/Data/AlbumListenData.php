<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Music\LogAlbumListenRequest;
use Carbon\Carbon;

final readonly class AlbumListenData
{
    public function __construct(
        public ?Carbon $listenedAt,
        public bool $yearOnly,
        public ?string $notes,
        public ?int $rating,
    ) {}

    public static function fromRequest(LogAlbumListenRequest $request): self
    {
        return new self(
            listenedAt: $request->validated('listened_at')
                ? Carbon::parse($request->validated('listened_at'))
                : null,
            yearOnly: (bool) $request->validated('year_only', false),
            notes: $request->validated('notes'),
            rating: $request->validated('rating') !== null ? (int) $request->validated('rating') : null,
        );
    }
}
