<?php

namespace App\Services;

use App\Models\Link;
use App\Models\LinkCheck;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HealthCheckService
{
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

            // status: down (error) | unhealth (slow > 1000ms) | up | down (non-2xx)
            if ($ms > 1000 && $response->successful()) {
                $status = 'unhealth';
            } elseif ($response->successful()) {
                $status = 'up';
            } else {
                $status = 'down';
            }

            $check = $link->checks()->create([
                'status' => $status,
                'http_status' => $response->status(),
                'response_time_ms' => $ms,
            ]);
        } catch (\Throwable $e) {
            $ms = (int) round((microtime(true) - $start) * 1000);

            $check = $link->checks()->create([
                'status' => 'down',
                'http_status' => null,
                'response_time_ms' => $ms,
                'error' => Str::limit($e->getMessage(), 1000),
            ]);
        }

        // update last_checked_at
        $link->forceFill(['last_checked_at' => now()])->save();

        return $check;
    }
}