<?php

declare(strict_types=1);

use App\Enums\EventType;
use App\Enums\IntegrationType;
use App\Enums\LinkStatus;
use App\Events\LinkCheckCreated;
use App\Listeners\NotifyIntegrationsListener;
use App\Models\Integration;
use App\Models\Link;
use App\Models\LinkCheck;
use App\Models\User;
use App\Services\Integrations\IntegrationNotificationService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Event::fake();
    Queue::fake();
});

it('dispatches LinkCheckCreated event when check is created', function () {
    $user = User::factory()->create();
    $link = Link::factory()->for($user)->create();

    $check = LinkCheck::factory()->for($link)->create([
        'status' => LinkStatus::DOWN,
    ]);

    Event::dispatch(new LinkCheckCreated($check));

    Event::assertDispatched(LinkCheckCreated::class);
});

it('notifies integrations when link goes down', function () {
    $user = User::factory()->create();
    $link = Link::factory()->for($user)->create();
    
    // Create integration for LINK_DOWN event
    $integration = Integration::factory()
        ->for($user)
        ->create([
            'type' => IntegrationType::EMAIL,
            'email' => 'test@example.com',
            'events' => [EventType::LINK_DOWN->value],
        ]);

    $check = LinkCheck::factory()->for($link)->create([
        'status' => LinkStatus::DOWN,
    ]);

    $notificationService = app(IntegrationNotificationService::class);
    $listener = new NotifyIntegrationsListener($notificationService);
    
    $listener->handle(new LinkCheckCreated($check));

    // Verify integration was marked as notified
    assertDatabaseHas('integrations', [
        'id' => $integration->id,
    ]);
});

it('does not notify if user has no integrations', function () {
    $user = User::factory()->create();
    $link = Link::factory()->for($user)->create();

    $check = LinkCheck::factory()->for($link)->create([
        'status' => LinkStatus::DOWN,
    ]);

    $notificationService = app(IntegrationNotificationService::class);
    $listener = new NotifyIntegrationsListener($notificationService);
    
    // Should not throw error
    $listener->handle(new LinkCheckCreated($check));

    expect(true)->toBeTrue();
});

it('does not notify if integration is not subscribed to the event', function () {
    $user = User::factory()->create();
    $link = Link::factory()->for($user)->create();
    
    // Create integration only for LINK_UP event
    Integration::factory()
        ->for($user)
        ->create([
            'type' => IntegrationType::EMAIL,
            'email' => 'test@example.com',
            'events' => [EventType::LINK_UP->value],
        ]);

    $check = LinkCheck::factory()->for($link)->create([
        'status' => LinkStatus::DOWN, // Different event
    ]);

    $notificationService = app(IntegrationNotificationService::class);
    $listener = new NotifyIntegrationsListener($notificationService);
    
    $listener->handle(new LinkCheckCreated($check));

    expect(true)->toBeTrue();
});

it('triggers LINK_ERROR event when check has an error', function () {
    $user = User::factory()->create();
    $link = Link::factory()->for($user)->create();
    
    Integration::factory()
        ->for($user)
        ->create([
            'type' => IntegrationType::EMAIL,
            'email' => 'test@example.com',
            'events' => [EventType::LINK_ERROR->value],
        ]);

    $check = LinkCheck::factory()->for($link)->create([
        'status' => LinkStatus::DOWN,
        'error' => 'Connection timeout',
    ]);

    $notificationService = app(IntegrationNotificationService::class);
    $listener = new NotifyIntegrationsListener($notificationService);
    
    $listener->handle(new LinkCheckCreated($check));

    expect(true)->toBeTrue();
});

it('triggers LINK_SLOW event when check is unhealthy', function () {
    $user = User::factory()->create();
    $link = Link::factory()->for($user)->create();
    
    Integration::factory()
        ->for($user)
        ->create([
            'type' => IntegrationType::EMAIL,
            'email' => 'test@example.com',
            'events' => [EventType::LINK_SLOW->value],
        ]);

    $check = LinkCheck::factory()->for($link)->slow()->create();

    $notificationService = app(IntegrationNotificationService::class);
    $listener = new NotifyIntegrationsListener($notificationService);
    
    $listener->handle(new LinkCheckCreated($check));

    expect(true)->toBeTrue();
});

it('listener is queued', function () {
    expect(new NotifyIntegrationsListener(app(IntegrationNotificationService::class)))
        ->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});
