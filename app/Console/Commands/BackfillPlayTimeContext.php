<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Music\ResolvePlayTimeContext;
use App\Models\Job;
use App\Models\Play;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class BackfillPlayTimeContext extends Command
{
    protected $signature = 'plays:backfill-time-context';

    protected $description = 'Resolve work/free time context for plays that have none yet';

    public function handle(ResolvePlayTimeContext $resolver): int
    {
        $jobs = Job::all();
        $total = Play::whereNull('time_context')->count();

        if ($total === 0) {
            $this->info('No plays to backfill.');

            return self::SUCCESS;
        }

        $this->info("Backfilling {$total} plays…");
        $bar = $this->output->createProgressBar($total);

        Play::whereNull('time_context')
            ->select(['id', 'played_at'])
            ->orderBy('id')
            ->chunkById(500, function ($plays) use ($resolver, $jobs, $bar) {
                $work = [];
                $free = [];

                foreach ($plays as $play) {
                    $ctx = $resolver->handle($play->played_at, $jobs);
                    if ($ctx === 'work') {
                        $work[] = $play->id;
                    } elseif ($ctx === 'free') {
                        $free[] = $play->id;
                    }
                    $bar->advance();
                }

                if ($work !== []) {
                    DB::table('plays')->whereIn('id', $work)->update(['time_context' => 'work']);
                }
                if ($free !== []) {
                    DB::table('plays')->whereIn('id', $free)->update(['time_context' => 'free']);
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
