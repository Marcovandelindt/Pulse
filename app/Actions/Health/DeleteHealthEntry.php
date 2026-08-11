<?php

declare(strict_types=1);

namespace App\Actions\Health;

use App\Models\HealthEntry;

final class DeleteHealthEntry
{
    public function handle(HealthEntry $entry): void
    {
        $entry->delete();
    }
}
