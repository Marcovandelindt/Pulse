<?php

declare(strict_types=1);

namespace App\Actions\Music;

use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class ResolvePlayTimeContext
{
    /**
     * @param  Collection<int, Job>  $jobs
     * @return 'work'|'free'|null
     */
    public function handle(Carbon $playedAt, Collection $jobs): ?string
    {
        $activeJobs = $jobs->filter(
            fn (Job $job) => $playedAt->gte($job->started_at->copy()->startOfDay())
                && ($job->ended_at === null || $playedAt->lte($job->ended_at->copy()->endOfDay()))
        );

        if ($activeJobs->isEmpty()) {
            return null;
        }

        foreach ($activeJobs as $job) {
            if ($job->wasWorkTime($playedAt)) {
                return 'work';
            }
        }

        return 'free';
    }
}
