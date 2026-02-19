<?php

use App\Models\User;

it('stores check_interval when creating a link', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/links', ['url' => 'https://example.com', 'check_interval' => 25])
        ->assertStatus(201)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('links', [
        'user_id' => $user->id,
        'check_interval' => 25,
    ]);
});
