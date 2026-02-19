<?php

namespace App\Console\Commands;

use App\Models\LinkCheck;
use App\Models\Link;
use Illuminate\Console\Command;

class PruneLinkChecks extends Command
{
    protected $signature = 'health:prune';

    protected $description = 'Prune old link check history according to user plan retention';

    public function handle(): int
    {
        $retention = [
            'free' => 7,      // days
            'starter' => 30,  // days
            'unlimited' => 365, // days
        ];

        foreach ($retention as $plan => $days) {
            // Collect IDs via join to avoid driver-specific delete syntax
            $ids = \Illuminate\Support\Facades\DB::table('link_checks')
                ->join('links', 'link_checks.link_id', '=', 'links.id')
                ->join('users', 'links.user_id', '=', 'users.id')
                ->where('users.plan', $plan)
                ->where('link_checks.created_at', '<', now()->subDays($days))
                ->pluck('link_checks.id');

            if ($ids->isEmpty()) {
                $this->info("Pruned 0 checks for plan {$plan}");
                continue;
            }

            $deleted = LinkCheck::whereIn('id', $ids)->delete();

            $this->info("Pruned {$deleted} checks for plan {$plan} (older than {$days} days)");
        }

        return 0;
    }
}
