<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Plan;
use App\Jobs\PruneLinkChecksJob;
use Illuminate\Console\Command;

class PruneLinkChecks extends Command
{
    protected $signature = 'health:prune';

    protected $description = 'Prune old link check history according to user plan retention';

    public function handle(): int
    {
        foreach (Plan::cases() as $plan) {
            $retentionDays = $plan->logsRetentionDays();
            
            PruneLinkChecksJob::dispatch($plan->value, $retentionDays);
            
            $this->info("Dispatched prune job for plan {$plan->displayName()} (retention: {$retentionDays} days)");
        }

        $this->info('All prune jobs have been dispatched to the queue');

        return 0;
    }
}
