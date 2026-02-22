<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LinkStatus;
use App\Events\LinkCheckCreated;
use App\Models\Link;
use App\Models\LinkCheck;
use App\Traits\DeterminesHealthStatus;
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
}