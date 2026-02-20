<?php

use App\Enums\EventType;
use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can list their integrations', function () {
    $user = User::factory()->create();
    
    Integration::factory()
        ->count(3)
        ->for($user)
        ->create();

    $response = $this->actingAs($user)->getJson('/api/integrations');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('user can create an email integration', function () {
    $user = User::factory()->create();
    
    $data = [
        'name' => 'My Email Integration',
        'type' => IntegrationType::EMAIL->value,
        'email' => 'test@example.com',
        'events' => [EventType::LINK_DOWN->value, EventType::LINK_UP->value],
    ];

    $response = $this->actingAs($user)->postJson('/api/integrations', $data);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'My Email Integration')
        ->assertJsonPath('data.type', IntegrationType::EMAIL->value)
        ->assertJsonPath('data.email', 'test@example.com');

    $this->assertDatabaseHas('integrations', [
        'user_id' => $user->id,
        'name' => 'My Email Integration',
        'type' => IntegrationType::EMAIL->value,
        'email' => 'test@example.com',
    ]);
});

test('user can create a slack integration', function () {
    $user = User::factory()->create();
    
    $data = [
        'name' => 'Slack Monitoring',
        'type' => IntegrationType::SLACK->value,
        'token' => 'xoxb-test-token',
        'channel_token' => 'https://hooks.slack.com/services/TEST',
        'events' => [EventType::LINK_DOWN->value],
    ];

    $response = $this->actingAs($user)->postJson('/api/integrations', $data);

    $response->assertCreated()
        ->assertJsonPath('data.type', IntegrationType::SLACK->value);

    $integration = Integration::first();
    expect($integration->token)->toBe('xoxb-test-token');
    expect($integration->channel_token)->toBe('https://hooks.slack.com/services/TEST');
});

test('user can create a discord integration', function () {
    $user = User::factory()->create();
    
    $data = [
        'name' => 'Discord Notifications',
        'type' => IntegrationType::DISCORD->value,
        'token' => 'https://discord.com/api/webhooks/123/abc',
        'user_token' => '1234567890',
        'events' => [EventType::LINK_ERROR->value],
    ];

    $response = $this->actingAs($user)->postJson('/api/integrations', $data);

    $response->assertCreated()
        ->assertJsonPath('data.type', IntegrationType::DISCORD->value);

    $integration = Integration::first();
    expect($integration->token)->toBe('https://discord.com/api/webhooks/123/abc');
    expect($integration->user_token)->toBe('1234567890');
});

test('validates required email for email integration', function () {
    $user = User::factory()->create();
    
    $data = [
        'name' => 'Test',
        'type' => IntegrationType::EMAIL->value,
        'events' => [EventType::LINK_DOWN->value],
    ];

    $response = $this->actingAs($user)->postJson('/api/integrations', $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('validates required fields for slack integration', function () {
    $user = User::factory()->create();
    
    $data = [
        'name' => 'Test',
        'type' => IntegrationType::SLACK->value,
        'events' => [EventType::LINK_DOWN->value],
    ];

    $response = $this->actingAs($user)->postJson('/api/integrations', $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['token', 'channel_token']);
});

test('validates required fields for discord integration', function () {
    $user = User::factory()->create();
    
    $data = [
        'name' => 'Test',
        'type' => IntegrationType::DISCORD->value,
        'events' => [EventType::LINK_DOWN->value],
    ];

    $response = $this->actingAs($user)->postJson('/api/integrations', $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['token', 'user_token']);
});

test('requires at least one event', function () {
    $user = User::factory()->create();
    
    $data = [
        'name' => 'Test',
        'type' => IntegrationType::EMAIL->value,
        'email' => 'test@example.com',
        'events' => [],
    ];

    $response = $this->actingAs($user)->postJson('/api/integrations', $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['events']);
});

test('user can update an integration', function () {
    $user = User::factory()->create();
    
    $integration = Integration::factory()
        ->email()
        ->for($user)
        ->create();

    $response = $this->actingAs($user)->putJson("/api/integrations/{$integration->id}", [
        'name' => 'Updated Name',
        'events' => [EventType::LINK_DOWN->value],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('integrations', [
        'id' => $integration->id,
        'name' => 'Updated Name',
    ]);
});

test('user can delete an integration', function () {
    $user = User::factory()->create();
    
    $integration = Integration::factory()
        ->for($user)
        ->create();

    $response = $this->actingAs($user)->deleteJson("/api/integrations/{$integration->id}");

    $response->assertOk();

    $this->assertDatabaseMissing('integrations', [
        'id' => $integration->id,
    ]);
});

test('user cannot access another user integration', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    
    $integration = Integration::factory()
        ->for($otherUser)
        ->create();

    $response = $this->actingAs($user)->getJson("/api/integrations/{$integration->id}");

    $response->assertForbidden();
});

test('user cannot update another user integration', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    
    $integration = Integration::factory()
        ->for($otherUser)
        ->create();

    $response = $this->actingAs($user)->putJson("/api/integrations/{$integration->id}", [
        'name' => 'Hacked',
    ]);

    $response->assertForbidden();
});

test('user cannot delete another user integration', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    
    $integration = Integration::factory()
        ->for($otherUser)
        ->create();

    $response = $this->actingAs($user)->deleteJson("/api/integrations/{$integration->id}");

    $response->assertForbidden();
});

test('user can show a single integration', function () {
    $user = User::factory()->create();
    
    $integration = Integration::factory()
        ->email()
        ->for($user)
        ->create();

    $response = $this->actingAs($user)->getJson("/api/integrations/{$integration->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $integration->id)
        ->assertJsonPath('data.name', $integration->name);
});
