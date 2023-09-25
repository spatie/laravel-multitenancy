<?php

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TestJob;

beforeEach(function () {
    Event::fake(JobFailed::class);

    config()->set('queue.default', 'database');
    config()->set('multitenancy.tenant_aware_jobs', [TestJob::class]);

    $this->tenant = Tenant::factory()->create();

    Event::assertNotDispatched(JobFailed::class);
});

it('succeeds with closure jobs when queues are tenant aware by default', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->tenant->execute(function (Tenant $currentTenant) {
        dispatch(function () use ($currentTenant) {
            $tenant = Tenant::current();

            expect($tenant)->not->toBeNull()
                ->and($tenant?->name)->toBe($currentTenant->name);
        });
    });

    $this->artisan('queue:work --once');
});

it('fails with closure jobs when queues are not tenant aware by default', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

    $this->tenant->execute(function (Tenant $currentTenant) {
        dispatch(function () {
            $tenant = Tenant::current();

            expect($tenant)->toBeNull();
        });
    });

    $this->artisan('queue:work --once');
});
