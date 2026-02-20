<?php

namespace App\Services\Integrations;

use App\Enums\EventType;
use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Models\Link;
use App\Models\LinkCheck;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class IntegrationNotificationService
{
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
            IntegrationType::EMAIL => $this->sendEmailNotification($integration, $message),
            IntegrationType::SLACK => $this->sendSlackNotification($integration, $message),
            IntegrationType::DISCORD => $this->sendDiscordNotification($integration, $message),
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
     * Send email notification.
     */
    protected function sendEmailNotification(Integration $integration, array $message): void
    {
        $email = $integration->email;
        
        if (!$email) {
            throw new \Exception('Email not configured for integration');
        }

        Mail::send('emails.integration-notification', $message, function ($mail) use ($email, $message) {
            $mail->to($email)
                ->subject($message['title']);
        });
    }

    /**
     * Send Slack notification.
     */
    protected function sendSlackNotification(Integration $integration, array $message): void
    {
        $channelToken = $integration->channel_token;
        
        if (!$channelToken) {
            throw new \Exception('Channel token not configured for Slack integration');
        }

        // Webhook URL do Slack
        $webhookUrl = $channelToken;

        $payload = [
            'text' => $message['title'],
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => $message['title'],
                    ],
                ],
                [
                    'type' => 'section',
                    'fields' => collect($message['details'])
                        ->map(fn ($value, $key) => [
                            'type' => 'mrkdwn',
                            'text' => "*{$key}:*\n{$value}",
                        ])
                        ->values()
                        ->all(),
                ],
            ],
        ];

        $response = Http::post($webhookUrl, $payload);

        if (!$response->successful()) {
            throw new \Exception("Slack notification failed: {$response->body()}");
        }
    }

    /**
     * Send Discord notification.
     */
    protected function sendDiscordNotification(Integration $integration, array $message): void
    {
        $webhookUrl = $integration->token;
        
        if (!$webhookUrl) {
            throw new \Exception('Webhook URL not configured for Discord integration');
        }

        // Discord embed structure
        $embed = [
            'title' => $message['title'],
            'description' => collect($message['details'])
                ->map(fn ($value, $key) => "**{$key}:** {$value}")
                ->join("\n"),
            'color' => match ($message['event']) {
                EventType::LINK_DOWN, EventType::LINK_ERROR => 15158332, // Red
                EventType::LINK_UP => 3066993, // Green
                EventType::LINK_SLOW => 15105570, // Orange
                default => 3447003, // Blue
            },
            'timestamp' => now()->toIso8601String(),
            'footer' => [
                'text' => 'Health Check Monitoring',
            ],
        ];

        $payload = [
            'embeds' => [$embed],
        ];

        $response = Http::post($webhookUrl, $payload);

        if (!$response->successful()) {
            throw new \Exception("Discord notification failed: {$response->body()}");
        }
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
            'event' => EventType::LINK_DOWN, // Apenas para o formato
        ];

        match ($integration->type) {
            IntegrationType::EMAIL => $this->sendEmailNotification($integration, $message),
            IntegrationType::SLACK => $this->sendSlackNotification($integration, $message),
            IntegrationType::DISCORD => $this->sendDiscordNotification($integration, $message),
        };
    }
}
