<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Models\Pattern;

final class DeletePattern
{
    public function handle(Pattern $pattern): void
    {
        $pattern->delete();
    }
}
