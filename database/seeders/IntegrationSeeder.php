<?php

namespace Database\Seeders;

use App\Enums\EventType;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Database\Seeder;

class IntegrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pega o primeiro usuário ou cria um
        $user = User::first();

        if (!$user) {
            $this->command->warn('Nenhum usuário encontrado. Execute UserSeeder primeiro.');
            return;
        }

        // Email integration
        Integration::create([
            'user_id' => $user->id,
            'name' => 'Notificações por E-mail',
            'type' => 'email',
            'email' => $user->email,
            'events' => [
                EventType::LINK_DOWN->value,
                EventType::LINK_UP->value,
            ],
            'metadata' => [
                'description' => 'Notificações principais por e-mail',
            ],
        ]);

        // Slack integration
        Integration::create([
            'user_id' => $user->id,
            'name' => 'Canal #monitoring Slack',
            'type' => 'slack',
            'token' => 'xoxb-example-token',
            'channel_token' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX',
            'events' => [
                EventType::LINK_DOWN->value,
                EventType::LINK_SLOW->value,
            ],
            'metadata' => [
                'channel' => '#monitoring',
                'description' => 'Notificações para o time',
            ],
        ]);

        // Discord integration
        Integration::create([
            'user_id' => $user->id,
            'name' => 'Notificações Discord',
            'type' => 'discord',
            'token' => 'https://discord.com/api/webhooks/1234567890/example-webhook-token',
            'user_token' => '1234567890',
            'events' => [
                EventType::LINK_DOWN->value,
                EventType::LINK_ERROR->value,
            ],
            'metadata' => [
                'channel' => '#monitoring',
                'description' => 'Envia mensagens para o canal Discord',
            ],
        ]);

        $this->command->info('Integrações criadas com sucesso!');
    }
}
