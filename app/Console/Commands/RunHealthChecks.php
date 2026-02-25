<?php

namespace App\Console\Commands;

use App\Jobs\PerformBatchLinkCheckJob;
use App\Models\Link;
use Illuminate\Console\Command;

class RunHealthChecks extends Command
{
    // optional interval argument (minutes): 1,5,15,30,60
    protected $signature = 'health:check {interval?} {--batch-size=100 : Number of links per concurrent batch}';

    protected $description = 'Dispatch health checks for links that are due (optionally for a specific interval)';

    public function handle(): int
    {
        $valid = [1, 5, 15, 30, 60];
        $batchSize = max(1, (int) $this->option('batch-size'));

        $interval = $this->argument('interval');
        $intervals = $valid;

        if ($interval) {
            $interval = (int) $interval;

            if (! in_array($interval, $valid, true)) {
                $this->error('Invalid interval. Allowed: ' . implode(',', $valid));

                return 1;
            }

            $intervals = [$interval];
        }

        $totalDispatched = 0;
        $batchCount = 0;

        foreach ($intervals as $i) {
            Link::where('check_interval', $i)
                ->chunkById($batchSize, function ($links) use (&$totalDispatched, &$batchCount) {
                    $due = $links
                        ->filter(fn (Link $l) => $l->isDueForCheck())
                        ->pluck('id')
                        ->values()
                        ->all();

                    if (empty($due)) {
                        return;
                    }

                    // Dispatch a single job that fires all requests simultaneously via Http::pool()
                    PerformBatchLinkCheckJob::dispatch($due);

                    $totalDispatched += count($due);
                    $batchCount++;
                });
        }

        $this->info("Dispatched {$totalDispatched} links across {$batchCount} concurrent batches.");

        return 0;
    }
}
