<?php

use App\Jobs\PerformLinkCheckJob;
use App\Models\Link;
use App\Models\User;
use App\Models\LinkCheck;
use Illuminate\Support\Facades\Http;

it('dispatches checks and persists history when health:check runs', function () {
    $user = User::factory()->create();

    $link = Link::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.test/ok',
        'check_interval' => 1,
        'last_checked_at' => null,
    ]);

    Http::fake([
        'https://example.test/*' => Http::response('ok', 200),
    ]);

    $this->artisan('health:check')->assertExitCode(0);

    $this->assertDatabaseHas('link_checks', [
        'link_id' => $link->id,
        'status' => 'up',
        'http_status' => 200,
    ]);

    $this->assertNotNull($link->fresh()->last_checked_at);
});
