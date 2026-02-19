<?php

use App\Models\User;

it('shows pricing page', function () {
    $response = $this->get('/plans');

    $response->assertStatus(200);
});

it('allows user to subscribe to starter (local simulation)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/subscribe', ['plan' => 'starter'])
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'plan' => 'starter',
        'links_quota' => 25,
    ]);
});
