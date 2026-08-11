<?php

declare(strict_types=1);

namespace App\Actions\Health;

use App\Http\Requests\Health\StoreHealthEntryRequest;
use App\Http\Requests\Health\UpdateHealthEntryRequest;
use Carbon\Carbon;

final readonly class HealthEntryData
{
    public function __construct(
        public Carbon $date,
        public ?int $steps,
        public ?string $notes,
    ) {}

    public static function fromRequest(StoreHealthEntryRequest|UpdateHealthEntryRequest $request): self
    {
        return new self(
            date: Carbon::parse($request->validated('date')),
            steps: $request->validated('steps') !== null ? (int) $request->validated('steps') : null,
            notes: $request->validated('notes'),
        );
    }
}
