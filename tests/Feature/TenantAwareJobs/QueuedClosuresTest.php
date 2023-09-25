<?php

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TestJob;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    Event::fake(JobFailed::class);

    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    config()->set('queue.default', 'database');
    config()->set('multitenancy.tenant_aware_jobs', [TestJob::class]);

    $this->tenant1 = Tenant::factory()->create();
    $this->tenant2 = Tenant::factory()->create();

    Event::assertNotDispatched(JobFailed::class);
});

it('succeeds with closure jobs', function () {
    $this->tenant1->execute(function (Tenant $tenant1) {
        dispatch(function () use ($tenant1) {
            $tenant = Tenant::current();

            expect($tenant)->not->toBeNull()
                ->and($tenant->name)->toBe($tenant1->name);
        });
    });

    $this->tenant2->execute(function (Tenant $tenant2) {
        dispatch(function () use ($tenant2) {
            $tenant = Tenant::current();

            expect($tenant)->not->toBeNull()
                ->and($tenant->name)->toBe($tenant2->name);
        });
    });

    $this->artisan('queue:work --once');
});
