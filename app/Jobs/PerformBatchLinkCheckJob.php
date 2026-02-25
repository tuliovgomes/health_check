<?php

namespace App\Jobs;

use App\Models\Link;
use App\Services\HealthCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PerformBatchLinkCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int>  $linkIds  IDs of the links to check concurrently
     */
    public function __construct(public readonly array $linkIds)
    {
        $this->onQueue('health-checks');
    }

    /**
     * Rehydrate the links and fire all HTTP requests simultaneously via Http::pool().
     */
    public function handle(HealthCheckService $service): void
    {
        $links = Link::whereIn('id', $this->linkIds)->get();

        $service->performBatch($links);
    }
}
