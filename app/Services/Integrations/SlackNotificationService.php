<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;

class SlackNotificationService
{
    /**
     * Send Slack notification.
     */
    public function send(Integration $integration, array $message): void
    {
        $token = $integration->token;
        $channelId = $integration->channel_token;
        
        $this->validateConfiguration($token, $channelId);
        
        $payload = $this->buildPayload($channelId, $message);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post('https://slack.com/api/chat.postMessage', $payload);

        if (!$response->successful()) {
            throw new \Exception("Slack notification failed: {$response->body()}");
        }

        $this->checkApiResponse($response->json());
    }

    /**
     * Validate Slack configuration.
     */
    protected function validateConfiguration(?string $token, ?string $channelId): void
    {
        if (!$token) {
            throw new \Exception('Bot token not configured for Slack integration');
        }

        if (!$channelId) {
            throw new \Exception('Channel ID not configured for Slack integration');
        }
    }

    /**
     * Build Slack message payload.
     */
    protected function buildPayload(string $channelId, array $message): array
    {
        return [
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
    }

    /**
     * Check Slack API response for errors.
     */
    protected function checkApiResponse(array $responseData): void
    {
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
}
