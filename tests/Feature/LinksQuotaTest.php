<?php

use App\Enums\Plan;
use App\Models\Link;
use App\Models\User;

it('blocks creating links when quota is reached', function () {
    $user = User::factory()->create();

    // assign free plan (quota 5)
    $user->assignPlan(Plan::FREE->value);

    Link::factory()->count(5)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson('/links', ['url' => 'https://example.com'])
        ->assertStatus(403)
        ->assertJson(['success' => false]);
});

it('allows creating links until quota is reached', function () {
    $user = User::factory()->create();

    $user->assignPlan(Plan::STARTER->value); // quota 25

    Link::factory()->count(24)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson('/links', ['url' => 'https://example.com'])
        ->assertStatus(201);

    expect($user->links()->count())->toBe(25);
});
