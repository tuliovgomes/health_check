<?php

use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('runs checks only for the specified interval when provided', function () {
    $user = User::factory()->create();

    $link1 = Link::factory()->create(['user_id' => $user->id, 'check_interval' => 1]);
    $link5 = Link::factory()->create(['user_id' => $user->id, 'check_interval' => 5]);

    Http::fake([
        '*' => Http::response('ok', 200),
    ]);

    // run only interval 5
    $this->artisan('health:check', ['interval' => 5])->assertExitCode(0);

    $this->assertDatabaseMissing('link_checks', ['link_id' => $link1->id]);
    $this->assertDatabaseHas('link_checks', ['link_id' => $link5->id]);
});
