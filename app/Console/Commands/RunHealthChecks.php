<?php

namespace App\Console\Commands;

use App\Jobs\PerformLinkCheckJob;
use App\Models\Link;
use Illuminate\Console\Command;

class RunHealthChecks extends Command
{
    // optional interval argument (minutes): 1,5,15,30,60
    protected $signature = 'health:check {interval?}';

    protected $description = 'Dispatch health checks for links that are due (optionally for a specific interval)';

    public function handle(): int
    {
        $valid = [1, 5, 15, 30, 60];

        $interval = $this->argument('interval');

        $totalDispatched = 0;

        $processInterval = function (int $i) use (&$totalDispatched) {
            Link::where('check_interval', $i)
                ->chunkById(100, function ($links) use (&$totalDispatched) {
                    $due = $links->filter(fn (Link $l) => $l->isDueForCheck());

                    foreach ($due as $link) {
                        PerformLinkCheckJob::dispatch($link);
                        $totalDispatched++;
                    }
                });
        };

        if ($interval) {
            $interval = (int) $interval;

            if (! in_array($interval, $valid, true)) {
                $this->error('Invalid interval. Allowed: ' . implode(',', $valid));

                return 1;
            }

            $processInterval($interval);
        } else {
            foreach ($valid as $i) {
                $processInterval($i);
            }
        }

        $this->info('Dispatched ' . $totalDispatched . ' health checks.');

        return 0;
    }
}
