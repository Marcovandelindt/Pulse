<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Models\Pattern;

final class CreatePattern
{
    public function handle(PatternData $data): Pattern
    {
        return Pattern::create([
            'title'       => $data->title,
            'description' => $data->description,
        ]);
    }
}
