<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LinkStatus;
use App\Events\LinkCheckCreated;
use App\Models\Link;
use App\Models\LinkCheck;
use App\Traits\DeterminesHealthStatus;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HealthCheckService
{
    use DeterminesHealthStatus;

    /**
     * Perform a health check for the given Link and persist a LinkCheck record.
     */
    public function perform(Link $link): LinkCheck
    {
        $start = microtime(true);

        try {
            $response = Http::timeout(10)->get($link->url);

            $msMeasured = (int) round((microtime(true) - $start) * 1000);
            // allow tests or upstream to provide a response-time header for simulation
            $ms = $response->header('X-Response-Time')
                ? (int) $response->header('X-Response-Time')
                : $msMeasured;

            // determine status via trait (rules map) — returns LinkStatus enum
            $statusEnum = $this->determineHealthStatusFromResponse($ms, $response->successful(), $response->status());

            $check = $link->checks()->create([
                'status' => $statusEnum->value,
                'http_status' => $response->status(),
                'response_time_ms' => $ms,
            ]);
        } catch (\Throwable $e) {
            $ms = (int) round((microtime(true) - $start) * 1000);

            $check = $link->checks()->create([
                'status' => LinkStatus::DOWN->value,
                'http_status' => null,
                'response_time_ms' => $ms,
                'error' => Str::limit($e->getMessage(), 1000),
            ]);
        }

        // update last_checked_at
        $link->forceFill(['last_checked_at' => now()])->save();

        // Dispatch event for notification listeners
        LinkCheckCreated::dispatch($check);

        return $check;
    }

    /**
     * Perform health checks for a batch of links concurrently using Http::pool().
     * All HTTP requests are fired simultaneously; results are processed after all complete.
     *
     * @param  Collection<int, Link>  $links
     * @return Collection<int, LinkCheck>
     */
    public function performBatch(Collection $links): Collection
    {
        $links = $links->values(); // ensure 0-based sequential integer keys
        $timings = [];             // will be populated via Guzzle transfer stats

        // Fire all requests simultaneously
        $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($links, &$timings) {
            return $links->map(function (Link $link, int $index) use ($pool, &$timings) {
                $key = (string) $index;

                return $pool
                    ->as($key)
                    ->timeout(10)
                    ->withOptions([
                        // Guzzle on_stats gives us the accurate per-request transfer time
                        'on_stats' => function (TransferStats $stats) use ($key, &$timings): void {
                            $timings[$key] = (int) round($stats->getTransferTime() * 1000);
                        },
                    ])
                    ->get($link->url);
            })->all();
        });

        // Process each response after all requests have completed
        $checks = collect();

        foreach ($links as $index => $link) {
            $key = (string) $index;
            $response = $responses[$key];
            $ms = $timings[$key] ?? 0;

            if ($response instanceof \Throwable) {
                $check = $link->checks()->create([
                    'status' => LinkStatus::DOWN->value,
                    'http_status' => null,
                    'response_time_ms' => $ms,
                    'error' => Str::limit($response->getMessage(), 1000),
                ]);
            } else {
                // X-Response-Time header takes precedence when present (useful for tests/simulation)
                $ms = $response->header('X-Response-Time')
                    ? (int) $response->header('X-Response-Time')
                    : $ms;

                $statusEnum = $this->determineHealthStatusFromResponse(
                    $ms,
                    $response->successful(),
                    $response->status()
                );

                $check = $link->checks()->create([
                    'status' => $statusEnum->value,
                    'http_status' => $response->status(),
                    'response_time_ms' => $ms,
                ]);
            }

            $link->forceFill(['last_checked_at' => now()])->save();
            LinkCheckCreated::dispatch($check);

            $checks->push($check);
        }

        return $checks;
    }
}
