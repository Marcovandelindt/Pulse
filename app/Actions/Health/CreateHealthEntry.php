<?php

declare(strict_types=1);

namespace App\Actions\Health;

use App\Models\HealthEntry;

final class CreateHealthEntry
{
    public function handle(HealthEntryData $data): HealthEntry
    {
        return HealthEntry::create([
            'date' => $data->date,
            'steps' => $data->steps,
            'notes' => $data->notes,
        ]);
    }
}
