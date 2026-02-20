<?php

use App\Enums\EventType;
use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Models\User;

it('can check if integration should notify for event', function () {
    $integration = Integration::factory()
        ->email()
        ->withEvents([EventType::LINK_DOWN->value])
        ->make();

    expect($integration->shouldNotifyFor(EventType::LINK_DOWN))->toBeTrue();
    expect($integration->shouldNotifyFor(EventType::LINK_UP))->toBeFalse();
});

it('encrypts and decrypts token', function () {
    $integration = Integration::factory()
        ->slack()
        ->create([
            'token' => 'secret-token',
        ]);

    // O token é encriptado no banco
    expect($integration->getRawOriginal('token'))->not->toBe('secret-token');

    // Mas pode ser lido normalmente
    expect($integration->token)->toBe('secret-token');
});

it('encrypts and decrypts user_token', function () {
    $integration = Integration::factory()
        ->discord()
        ->create([
            'user_token' => '1234567890',
        ]);

    expect($integration->getRawOriginal('user_token'))->not->toBe('1234567890');
    expect($integration->user_token)->toBe('1234567890');
});

it('encrypts and decrypts channel_token', function () {
    $integration = Integration::factory()
        ->slack()
        ->create([
            'channel_token' => 'https://hooks.slack.com/test',
        ]);

    expect($integration->getRawOriginal('channel_token'))->not->toBe('https://hooks.slack.com/test');
    expect($integration->channel_token)->toBe('https://hooks.slack.com/test');
});

it('can get event types', function () {
    $integration = Integration::factory()
        ->email()
        ->withEvents([EventType::LINK_DOWN->value, EventType::LINK_UP->value])
        ->make();

    $events = $integration->getEventTypes();

    expect($events)->toHaveCount(2);
    expect($events[0])->toBeInstanceOf(EventType::class);
    expect($events[0])->toBe(EventType::LINK_DOWN);
});

it('filters integrations by type with scope', function () {
    $user = User::factory()->create();

    Integration::factory()
        ->email()
        ->for($user)
        ->count(2)
        ->create();

    Integration::factory()
        ->slack()
        ->for($user)
        ->create();

    $emailIntegrations = Integration::ofType(IntegrationType::EMAIL)->get();

    expect($emailIntegrations)->toHaveCount(2);
});

it('filters integrations by event with scope', function () {
    $user = User::factory()->create();

    Integration::factory()
        ->for($user)
        ->withEvents([EventType::LINK_DOWN->value])
        ->count(2)
        ->create();

    Integration::factory()
        ->for($user)
        ->withEvents([EventType::LINK_UP->value])
        ->create();

    $linkDownIntegrations = Integration::forEvent(EventType::LINK_DOWN)->get();

    expect($linkDownIntegrations)->toHaveCount(2);
});

it('updates last notification timestamp', function () {
    $integration = Integration::factory()
        ->email()
        ->create([
            'last_notification_at' => null,
        ]);

    expect($integration->last_notification_at)->toBeNull();

    $integration->markAsNotified();
    $integration->refresh();

    expect($integration->last_notification_at)->not->toBeNull();
});

it('belongs to a user', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()
        ->for($user)
        ->create();

    expect($integration->user)->toBeInstanceOf(User::class);
    expect($integration->user->id)->toBe($user->id);
});

it('hides sensitive fields', function () {
    $integration = Integration::factory()
        ->slack()
        ->create();

    $array = $integration->toArray();

    expect($array)->not->toHaveKey('token');
    expect($array)->not->toHaveKey('user_token');
    expect($array)->not->toHaveKey('channel_token');
});

it('casts type to enum', function () {
    $integration = Integration::factory()
        ->email()
        ->create();

    expect($integration->type)->toBeInstanceOf(IntegrationType::class);
    expect($integration->type)->toBe(IntegrationType::EMAIL);
});

it('casts events to array', function () {
    $integration = Integration::factory()
        ->withEvents([EventType::LINK_DOWN->value])
        ->create();

    expect($integration->events)->toBeArray();
    expect($integration->events)->toContain(EventType::LINK_DOWN->value);
});

it('casts metadata to array', function () {
    $integration = Integration::factory()
        ->create([
            'metadata' => ['key' => 'value'],
        ]);

    expect($integration->metadata)->toBeArray();
    expect($integration->metadata)->toHaveKey('key');
});

it('returns required fields for email type', function () {
    expect(IntegrationType::EMAIL->requiredFields())
        ->toBe(['email']);
});

it('returns required fields for slack type', function () {
    expect(IntegrationType::SLACK->requiredFields())
        ->toBe(['token', 'channel_token']);
});

it('returns required fields for discord type', function () {
    expect(IntegrationType::DISCORD->requiredFields())
        ->toBe(['token', 'user_token']);
});
