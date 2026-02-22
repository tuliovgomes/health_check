<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\LinkCheck;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LinkCheckCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly LinkCheck $linkCheck,
    ) {}
}
