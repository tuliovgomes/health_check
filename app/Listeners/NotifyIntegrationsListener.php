<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\EventType;
use App\Events\LinkCheckCreated;
use App\Services\Integrations\IntegrationNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyIntegrationsListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        protected readonly IntegrationNotificationService $notificationService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(LinkCheckCreated $event): void
    {
        $linkCheck = $event->linkCheck;
        
        // Ensure link and user relationships are loaded
        $linkCheck->loadMissing('link.user.integrations');
        
        $link = $linkCheck->link;

        // Determine event type based on check status
        $eventType = $this->getEventTypeFromCheck($linkCheck);

        if (!$eventType) {
            return;
        }

        try {
            // Check if the user has integrations for this event
            $hasIntegrations = $link->user
                ->integrations()
                ->forEvent($eventType)
                ->exists();

            if (!$hasIntegrations) {
                return;
            }

            // Notify all active integrations
            $this->notificationService->notifyEvent($eventType, $link, $linkCheck);
        } catch (\Exception $e) {
            Log::error('Failed to notify integrations for link check', [
                'link_check_id' => $linkCheck->id,
                'link_id' => $link->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine the event type from the link check status.
     */
    protected function getEventTypeFromCheck(object $linkCheck): ?EventType
    {
        // If there's an error, always trigger LINK_ERROR event
        if (!empty($linkCheck->error)) {
            return EventType::LINK_ERROR;
        }

        return match ($linkCheck->status->value) {
            'down' => EventType::LINK_DOWN,
            'up' => EventType::LINK_UP,
            'unhealth' => EventType::LINK_SLOW,
            default => null,
        };
    }
}
