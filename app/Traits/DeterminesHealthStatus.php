<?php

namespace App\Traits;

use App\Enums\LinkStatus;
use Illuminate\Support\Arr;

trait DeterminesHealthStatus
{
    /**
     * Determine LinkStatus from response characteristics using rule map.
     * Rules are evaluated in order — first match wins.
     */
    protected function determineHealthStatusFromResponse(int $responseTimeMs, bool $successful, ?int $httpStatus = null): LinkStatus
    {
        $rules = [
            [LinkStatus::DOWN, fn($ms, $ok, $code) => ! $ok],
            [LinkStatus::UNHEALTH, fn($ms, $ok, $code) => $ok && $ms > 1000],
            [LinkStatus::HEALTHY, fn($ms, $ok, $code) => $ok && $ms <= 1000],
        ];

        foreach ($rules as [$status, $predicate]) {
            if ($predicate($responseTimeMs, $successful, $httpStatus)) {
                return $status;
            }
        }

        return LinkStatus::DOWN;
    }
}
