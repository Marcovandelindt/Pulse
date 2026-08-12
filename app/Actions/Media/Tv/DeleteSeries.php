<?php

declare(strict_types=1);

namespace App\Actions\Media\Tv;

use App\Models\TvSeries;

final class DeleteSeries
{
    public function handle(TvSeries $series): void
    {
        $series->delete();
    }
}
