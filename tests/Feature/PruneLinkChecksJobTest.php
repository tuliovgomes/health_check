<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Jobs\PruneLinkChecksJob;
use App\Models\Link;
use App\Models\LinkCheck;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('dispatches prune jobs for all plans', function () {
    Queue::fake();
    
    artisan('health:prune')
        ->assertSuccessful();

    Queue::assertPushed(PruneLinkChecksJob::class, 3); // FREE, STARTER, UNLIMITED
});

it('deletes old link checks according to plan retention', function () {
    $freeUser = User::factory()->create(['plan' => Plan::FREE->value]);
    $starterUser = User::factory()->create(['plan' => Plan::STARTER->value]);

    $freeLink = Link::factory()->for($freeUser)->create();
    $starterLink = Link::factory()->for($starterUser)->create();

    // Create old checks for FREE plan (retention: 7 days)
    LinkCheck::factory()->for($freeLink)->create(['created_at' => now()->subDays(10)]);
    LinkCheck::factory()->for($freeLink)->create(['created_at' => now()->subDays(8)]);
    
    // Create recent checks for FREE plan
    LinkCheck::factory()->for($freeLink)->create(['created_at' => now()->subDays(5)]);

    // Create old checks for STARTER plan (retention: 30 days)
    LinkCheck::factory()->for($starterLink)->create(['created_at' => now()->subDays(40)]);
    
    // Create recent checks for STARTER plan
    LinkCheck::factory()->for($starterLink)->create(['created_at' => now()->subDays(20)]);

    assertDatabaseCount('link_checks', 5);

    // Execute job for FREE plan
    $job = new PruneLinkChecksJob(Plan::FREE->value, Plan::FREE->logsRetentionDays());
    $job->handle();

    // Should delete 2 old checks from FREE plan
    assertDatabaseCount('link_checks', 3);

    // Execute job for STARTER plan
    $job = new PruneLinkChecksJob(Plan::STARTER->value, Plan::STARTER->logsRetentionDays());
    $job->handle();

    // Should delete 1 old check from STARTER plan
    assertDatabaseCount('link_checks', 2);
});

it('does not delete checks if none are older than retention period', function () {
    $user = User::factory()->create(['plan' => Plan::FREE->value]);
    $link = Link::factory()->for($user)->create();

    // Create only recent checks
    LinkCheck::factory()->for($link)->count(3)->create(['created_at' => now()->subDays(5)]);

    assertDatabaseCount('link_checks', 3);

    $job = new PruneLinkChecksJob(Plan::FREE->value, Plan::FREE->logsRetentionDays());
    $job->handle();

    // No checks should be deleted
    assertDatabaseCount('link_checks', 3);
});

it('only deletes checks for the specified plan', function () {
    $freeUser = User::factory()->create(['plan' => Plan::FREE->value]);
    $starterUser = User::factory()->create(['plan' => Plan::STARTER->value]);

    $freeLink = Link::factory()->for($freeUser)->create();
    $starterLink = Link::factory()->for($starterUser)->create();

    // Create old checks for both plans
    LinkCheck::factory()->for($freeLink)->create(['created_at' => now()->subDays(10)]);
    LinkCheck::factory()->for($starterLink)->create(['created_at' => now()->subDays(40)]);

    assertDatabaseCount('link_checks', 2);

    // Execute job only for FREE plan
    $job = new PruneLinkChecksJob(Plan::FREE->value, Plan::FREE->logsRetentionDays());
    $job->handle();

    // Should delete only the FREE plan check
    assertDatabaseCount('link_checks', 1);
    
    // STARTER check should still exist
    assertDatabaseHas('link_checks', [
        'link_id' => $starterLink->id,
    ]);
});

it('has correct job tags', function () {
    $job = new PruneLinkChecksJob(Plan::FREE->value, 7);
    
    expect($job->tags())->toContain('prune', 'link-checks', 'plan:free');
});
