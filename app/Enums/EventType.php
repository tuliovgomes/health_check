<?php

namespace App\Enums;

enum EventType: string
{
    case LINK_DOWN = 'link_down';
    case LINK_UP = 'link_up';
    case LINK_SLOW = 'link_slow';
    case LINK_ERROR = 'link_error';

    public function label(): string
    {
        return match ($this) {
            self::LINK_DOWN => 'Link Fora do Ar',
            self::LINK_UP => 'Link Restaurado',
            self::LINK_SLOW => 'Link Lento',
            self::LINK_ERROR => 'Erro no Link',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::LINK_DOWN => 'Notifica quando um link fica fora do ar',
            self::LINK_UP => 'Notifica quando um link volta a funcionar',
            self::LINK_SLOW => 'Notifica quando um link está respondendo lentamente',
            self::LINK_ERROR => 'Notifica quando há erro na verificação',
        };
    }
}
