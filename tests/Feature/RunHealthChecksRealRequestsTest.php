<?php

use App\Models\Link;
use App\Models\User;
use App\Models\LinkCheck;

/**
 * Real-HTTP integration test for the health:check command.
 *
 * No Http::fake() — every URL in url_test.json receives an actual request
 * via Http::pool() inside PerformBatchLinkCheckJob.
 *
 * Queue is set to "sync" so jobs execute inline during the test.
 * The test will take a while (real network I/O for ~192 URLs per batch).
 */
beforeEach(function () {
    // Force jobs to run synchronously — no worker required, real requests fire immediately.
    config(['queue.default' => 'sync']);
});

it('performs real concurrent health checks for every URL in url_test.json', function () {
    $user = User::factory()->create();

    $urls = collect(json_decode(file_get_contents(base_path('tests/url_test.json')), true));

    expect($urls)->not->toBeEmpty();

    // Prefix bare domains with https:// if needed
    $links = $urls->map(fn (string $url) => Link::factory()->create([
        'user_id'        => $user->id,
        'url'            => str_starts_with($url, 'http') ? $url : 'https://' . $url,
        'check_interval' => 1,
        'last_checked_at' => null,
    ]));

    // Run the command — dispatches PerformBatchLinkCheckJob in chunks of 100.
    // Because queue is "sync", jobs run immediately and fire real Http::pool() requests.
    $this->artisan('health:check')
        ->assertExitCode(0);

    // Every link must have at least one LinkCheck record persisted
    $links->each(function (Link $link) {
        expect(
            LinkCheck::where('link_id', $link->id)->exists()
        )->toBeTrue("No LinkCheck found for link [{$link->url}]");
    });

    // Additionally assert that last_checked_at was touched for every link
    $links->each(function (Link $link) {
        expect($link->fresh()->last_checked_at)->not->toBeNull(
            "last_checked_at not set for link [{$link->url}]"
        );
    });
});

it('stores a valid http_status or marks the link as down when host is unreachable', function () {
    $user = User::factory()->create();

    $urls = collect(
        json_decode(file_get_contents(base_path('tests/url_test.json')), true)
    );

    $links = $urls->map(fn (string $url) => Link::factory()->create([
        'user_id'         => $user->id,
        'url'             => str_starts_with($url, 'http') ? $url : 'https://' . $url,
        'check_interval'  => 1,
        'last_checked_at' => null,
    ]));

    $this->artisan('health:check')->assertExitCode(0);

    $links->each(function (Link $link) {
        $check = LinkCheck::where('link_id', $link->id)->latest()->first();

        expect($check)->not->toBeNull();

        // Status must be one of the valid enum values
        expect($check->status->value)->toBeIn(['healthy', 'unhealth', 'down']);

        // Response time must be recorded (>= 0)
        expect($check->response_time_ms)->toBeGreaterThanOrEqual(0);

        // If not down, http_status must be a valid HTTP code
        if ($check->status->value !== 'down') {
            expect($check->http_status)->toBeGreaterThanOrEqual(100);
        }
    });
});
