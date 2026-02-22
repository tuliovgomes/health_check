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
        $token = $integration->token;
        $channelId = $integration->channel_token;
        
        if (!$token) {
            throw new \Exception('Bot token not configured for Slack integration');
        }

        if (!$channelId) {
            throw new \Exception('Channel ID not configured for Slack integration');
        }

        $payload = [
            'channel' => $channelId,
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

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post('https://slack.com/api/chat.postMessage', $payload);

        if (!$response->successful()) {
            throw new \Exception("Slack notification failed: {$response->body()}");
        }

        // Check Slack API response for errors
        $responseData = $response->json();
        if (!($responseData['ok'] ?? false)) {
            $error = $responseData['error'] ?? 'Unknown error';
            
            $errorMessages = [
                'missing_scope' => 'Bot token sem permissões necessárias. Adicione o scope "chat:write" nas configurações do Slack App (OAuth & Permissions).',
                'not_in_channel' => 'Bot não está no canal. Adicione o bot ao canal usando /invite @nome_do_bot',
                'channel_not_found' => 'Canal não encontrado. Verifique se o Channel ID está correto.',
                'invalid_auth' => 'Token inválido. Verifique se o Bot Token está correto e começa com "xoxb-".',
                'token_revoked' => 'Token revogado. Gere um novo Bot Token nas configurações do Slack App.',
            ];
            
            $message = $errorMessages[$error] ?? "Erro na API do Slack: {$error}";
            throw new \Exception($message);
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
            'event' => EventType::LINK_DOWN,
        ];

        match ($integration->type) {
            IntegrationType::EMAIL => $this->sendEmailNotification($integration, $message),
            IntegrationType::SLACK => $this->sendSlackNotification($integration, $message),
            IntegrationType::DISCORD => $this->sendDiscordNotification($integration, $message),
        };
    }
}
