<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Enums\EventType;
use App\Models\Integration;
use Illuminate\Support\Facades\Http;

class DiscordNotificationService
{
    /**
     * Send Discord notification.
     */
    public function send(Integration $integration, array $message): void
    {
        $webhookUrl = $integration->token;
        
        if (!$webhookUrl) {
            throw new \Exception('Webhook URL not configured for Discord integration');
        }

        $payload = $this->buildPayload($message);
        
        $response = Http::post($webhookUrl, $payload);

        if (!$response->successful()) {
            throw new \Exception("Discord notification failed: {$response->body()}");
        }
    }

    /**
     * Build Discord embed payload.
     */
    protected function buildPayload(array $message): array
    {
        $embed = [
            'title' => $message['title'],
            'description' => collect($message['details'])
                ->map(fn ($value, $key) => "**{$key}:** {$value}")
                ->join("\n"),
            'color' => $this->getColorForEvent($message['event']),
            'timestamp' => now()->toIso8601String(),
            'footer' => [
                'text' => 'Health Check Monitoring',
            ],
        ];

        return [
            'embeds' => [$embed],
        ];
    }

    /**
     * Get embed color based on event type.
     */
    protected function getColorForEvent(EventType $event): int
    {
        return match ($event) {
            EventType::LINK_DOWN, EventType::LINK_ERROR => 15158332, // Red
            EventType::LINK_UP => 3066993, // Green
            EventType::LINK_SLOW => 15105570, // Orange
            default => 3447003, // Blue
        };
    }
}
