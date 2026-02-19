<?php

namespace App\Enums;

enum LinkStatus: string
{
    case UP = 'up';
    case DOWN = 'down';
    case UNHEALTH = 'unhealth';

    public function label(): string
    {
        return match ($this) {
            self::UP => 'Up',
            self::UNHEALTH => 'Unhealthy (slow)',
            self::DOWN => 'Down',
        };
    }
}
