<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Models\Pattern;

final class UpdatePattern
{
    public function handle(Pattern $pattern, PatternData $data): Pattern
    {
        $pattern->update([
            'title'       => $data->title,
            'description' => $data->description,
        ]);

        return $pattern;
    }
}
