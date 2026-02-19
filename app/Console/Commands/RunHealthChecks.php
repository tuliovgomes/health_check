<?php

namespace App\Console\Commands;

use App\Jobs\PerformLinkCheckJob;
use App\Models\Link;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunHealthChecks extends Command
{
    protected $signature = 'health:check';

    protected $description = 'Dispatch health checks for links that are due';

    public function handle(): int
    {
        // DB-agnostic approach: load recent links and filter in PHP using model helper.
        // This is safe for moderate number of links and avoids driver-specific SQL.
        $links = Link::all()->filter(fn (Link $l) => $l->isDueForCheck());

        foreach ($links as $link) {
            PerformLinkCheckJob::dispatch($link);
        }

        $this->info('Dispatched ' . $links->count() . ' health checks.');

        return 0;
    }
}
