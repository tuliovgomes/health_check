<?php

use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('records status unhealth when response time is > 1s', function () {
    $user = User::factory()->create();

    $link = Link::factory()->create([
        'user_id' => $user->id,
        'check_interval' => 1,
        'last_checked_at' => null,
    ]);

    // Simulate a slow, but successful response by setting X-Response-Time header
    Http::fake([
        '*' => Http::response('ok', 200, ['X-Response-Time' => '1500']),
    ]);

    $this->artisan('health:check', ['interval' => 1])->assertExitCode(0);

    $this->assertDatabaseHas('link_checks', [
        'link_id' => $link->id,
        'status' => 'unhealth',
        'response_time_ms' => 1500,
    ]);
});
