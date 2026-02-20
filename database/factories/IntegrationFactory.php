<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Enums\IntegrationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Integration>
 */
class IntegrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(IntegrationType::cases());
        
        $baseData = [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'type' => $type,
            'events' => fake()->randomElements(
                array_column(EventType::cases(), 'value'),
                fake()->numberBetween(1, 3)
            ),
            'metadata' => [
                'created_by' => 'factory',
                'environment' => 'testing',
            ],
            'last_notification_at' => fake()->boolean(50) ? fake()->dateTimeBetween('-7 days') : null,
        ];

        return match ($type) {
            IntegrationType::EMAIL => array_merge($baseData, [
                'email' => fake()->safeEmail(),
            ]),
            IntegrationType::SLACK => array_merge($baseData, [
                'token' => 'xoxb-' . fake()->uuid(),
                'channel_token' => 'https://hooks.slack.com/services/' . fake()->uuid(),
                'user_token' => fake()->optional()->uuid(),
            ]),
            IntegrationType::DISCORD => array_merge($baseData, [
                'token' => 'https://discord.com/api/webhooks/' . fake()->numerify('##########') . '/' . fake()->uuid(),
                'user_token' => fake()->optional()->numerify('##########'),
                'channel_token' => fake()->optional()->uuid(),
            ]),
        };
    }

    /**
     * Indicate that the integration is of type email.
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => IntegrationType::EMAIL,
            'email' => fake()->safeEmail(),
            'token' => null,
            'user_token' => null,
            'channel_token' => null,
        ]);
    }

    /**
     * Indicate that the integration is of type Slack.
     */
    public function slack(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => IntegrationType::SLACK,
            'email' => null,
            'token' => 'xoxb-' . fake()->uuid(),
            'channel_token' => 'https://hooks.slack.com/services/' . fake()->uuid(),
            'user_token' => fake()->optional()->uuid(),
        ]);
    }

    /**
     * Indicate that the integration is of type Discord.
     */
    public function discord(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => IntegrationType::DISCORD,
            'email' => null,
            'token' => 'https://discord.com/api/webhooks/' . fake()->numerify('##########') . '/' . fake()->uuid(),
            'user_token' => fake()->optional()->numerify('##########'),
            'channel_token' => fake()->optional()->uuid(),
        ]);
    }

    /**
     * Set specific events.
     */
    public function withEvents(array $events): static
    {
        return $this->state(fn (array $attributes) => [
            'events' => $events,
        ]);
    }
}
