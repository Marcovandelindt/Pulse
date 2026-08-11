<?php

declare(strict_types=1);

namespace App\Actions\Health;

use App\Models\HealthEntry;

final class UpdateHealthEntry
{
    public function handle(HealthEntry $entry, HealthEntryData $data): HealthEntry
    {
        $entry->update([
            'steps' => $data->steps,
            'notes' => $data->notes,
        ]);

        return $entry->fresh();
    }
}
