<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Enums\EventType;
use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Models\Link;
use App\Models\LinkCheck;
use Illuminate\Support\Facades\Log;

class IntegrationNotificationService
{
    public function __construct(
        protected readonly EmailNotificationService $emailService,
        protected readonly SlackNotificationService $slackService,
        protected readonly DiscordNotificationService $discordService,
    ) {}

    /**
     * Notify all active integrations for a given event.
     */
    public function notifyEvent(EventType $event, Link $link, ?LinkCheck $check = null): void
    {
        $integrations = Integration::query()
            ->where('user_id', $link->user_id)
            ->forEvent($event)
            ->get();

        foreach ($integrations as $integration) {
            try {
                $this->sendNotification($integration, $event, $link, $check);
                $integration->markAsNotified();
            } catch (\Exception $e) {
                Log::error('Failed to send integration notification', [
                    'integration_id' => $integration->id,
                    'event' => $event->value,
                    'link_id' => $link->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send notification to a specific integration.
     */
    protected function sendNotification(
        Integration $integration,
        EventType $event,
        Link $link,
        ?LinkCheck $check
    ): void {
        $message = $this->buildMessage($event, $link, $check);

        match ($integration->type) {
            IntegrationType::EMAIL => $this->emailService->send($integration, $message),
            IntegrationType::SLACK => $this->slackService->send($integration, $message),
            IntegrationType::DISCORD => $this->discordService->send($integration, $message),
        };
    }

    /**
     * Build notification message.
     */
    protected function buildMessage(EventType $event, Link $link, ?LinkCheck $check): array
    {
        $title = $event->label();
        
        $details = [
            'Link' => $link->title ?: $link->code,
            'URL' => $link->url,
            'Evento' => $event->label(),
            'Data/Hora' => now()->format('d/m/Y H:i:s'),
        ];

        if ($check) {
            $details['Status HTTP'] = $check->http_status ?? 'N/A';
            $details['Tempo de Resposta'] = $check->response_time_ms ? "{$check->response_time_ms}ms" : 'N/A';
            $details['Status'] = $check->status->label();
        }

        return [
            'title' => $title,
            'details' => $details,
            'link' => $link,
            'check' => $check,
            'event' => $event,
        ];
    }

    /**
     * Send test notification.
     */
    public function sendTestNotification(Integration $integration): void
    {
        $message = [
            'title' => '🧪 Teste de Integração - Health Check',
            'details' => [
                'Tipo' => $integration->type->label(),
                'Nome' => $integration->name,
                'Data/Hora' => now()->format('d/m/Y H:i:s'),
                'Status' => 'Esta é uma notificação de teste',
            ],
            'event' => EventType::LINK_DOWN,
        ];

        match ($integration->type) {
            IntegrationType::EMAIL => $this->emailService->send($integration, $message),
            IntegrationType::SLACK => $this->slackService->send($integration, $message),
            IntegrationType::DISCORD => $this->discordService->send($integration, $message),
        };
    }
}
