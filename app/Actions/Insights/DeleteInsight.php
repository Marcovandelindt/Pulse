<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Models\Insight;

final class DeleteInsight
{
    public function handle(Insight $insight): void
    {
        $insight->delete();
    }
}
