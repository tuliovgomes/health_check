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

            $ms = (int) round((microtime(true) - $start) * 1000);

            $check = $link->checks()->create([
                'status' => $response->successful() ? 'up' : 'down',
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