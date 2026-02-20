<?php

namespace App\Enums;

enum IntegrationType: string
{
    case EMAIL = 'email';
    case SLACK = 'slack';
    case DISCORD = 'discord';

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => 'E-mail',
            self::SLACK => 'Slack',
            self::DISCORD => 'Discord',
        };
    }

    public function requiredFields(): array
    {
        return match ($this) {
            self::EMAIL => ['email'],
            self::SLACK => ['token', 'channel_token'],
            self::DISCORD => ['token', 'user_token'],
        };
    }
}
