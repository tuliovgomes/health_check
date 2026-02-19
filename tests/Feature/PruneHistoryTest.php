<?php

use App\Models\Link;
use App\Models\LinkCheck;
use App\Models\User;

it('prunes link checks according to plan retention', function () {
    $user = User::factory()->create(['plan' => 'free']);

    $link = Link::factory()->create(['user_id' => $user->id]);

    // old (should be pruned for free = 7 days)
    $old = LinkCheck::create([
        'link_id' => $link->id,
        'status' => 'up',
        'http_status' => 200,
        'response_time_ms' => 10,
    ]);

    // recent (should remain)
    $recent = LinkCheck::create([
        'link_id' => $link->id,
        'status' => 'up',
        'http_status' => 200,
        'response_time_ms' => 10,
    ]);

    // ensure created_at timestamps are set correctly (mass assignment won't set created_at)
    \Illuminate\Support\Facades\DB::table('link_checks')->where('id', $old->id)
        ->update(['created_at' => now()->subDays(8), 'updated_at' => now()->subDays(8)]);

    \Illuminate\Support\Facades\DB::table('link_checks')->where('id', $recent->id)
        ->update(['created_at' => now()->subDays(6), 'updated_at' => now()->subDays(6)]);

    $this->artisan('health:prune')->assertExitCode(0);

    $this->assertDatabaseMissing('link_checks', ['id' => $old->id]);
    $this->assertDatabaseHas('link_checks', ['id' => $recent->id]);
});
