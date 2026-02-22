<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\LinkCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneLinkChecksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $plan,
        public readonly int $retentionDays,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoffDate = now()->subDays($this->retentionDays);

        // Collect IDs via join to avoid driver-specific delete syntax
        $ids = DB::table('link_checks')
            ->join('links', 'link_checks.link_id', '=', 'links.id')
            ->join('users', 'links.user_id', '=', 'users.id')
            ->where('users.plan', $this->plan)
            ->where('link_checks.created_at', '<', $cutoffDate)
            ->pluck('link_checks.id');

        if ($ids->isEmpty()) {
            Log::info("Pruned 0 checks for plan {$this->plan}");
            return;
        }

        $deleted = LinkCheck::whereIn('id', $ids)->delete();

        Log::info("Pruned {$deleted} checks for plan {$this->plan} (older than {$this->retentionDays} days)");
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['prune', 'link-checks', "plan:{$this->plan}"];
    }
}
