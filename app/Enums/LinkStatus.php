<?php

namespace App\Enums;

enum LinkStatus: string
{
    case HEALTHY = 'healthy';
    case DOWN = 'down';
    case UNHEALTH = 'unhealth';

    public function label(): string
    {
        return match ($this) {
            self::HEALTHY => 'Healthy',
            self::UNHEALTH => 'Unhealthy (slow)',
            self::DOWN => 'Down',
        };
    }
}
