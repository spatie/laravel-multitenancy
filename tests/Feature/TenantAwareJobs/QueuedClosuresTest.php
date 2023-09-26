<?php

use Illuminate\Queue\CallQueuedClosure;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    config()->set('queue.default', 'database');
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);
    config()->set('multitenancy.queueable_to_job', [
        CallQueuedClosure::class => 'closure',
    ]);

    $this->tenant = Tenant::factory()->create();
});

it('succeeds with closure job when queues are tenant aware by default', function () {
    $valuestore = Valuestore::make(tempFile('tenantAware.json'))->flush();

    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->tenant->makeCurrent();

    dispatch(function () use($valuestore) {
        $tenant = Tenant::current();

        $valuestore->put('tenantId', $tenant?->getKey());
        $valuestore->put('tenantName', $tenant?->name);
    });

    $this->artisan('queue:work --once');

    expect($valuestore->get('tenantId'))->toBe($this->tenant->getKey())
        ->and($valuestore->get('tenantName'))->toBe($this->tenant->name);
});

it('fails with closure job when queues are not tenant aware by default', function () {
    $valuestore = Valuestore::make(tempFile('tenantAware.json'))->flush();

    $this->tenant->makeCurrent();

    dispatch(function () use ($valuestore) {
        $tenant = Tenant::current();

        $valuestore->put('tenantId', $tenant?->getKey());
        $valuestore->put('tenantName', $tenant?->name);
    });

    $this->artisan('queue:work --once');

    expect($valuestore->get('tenantId'))->toBeNull()
        ->and($valuestore->get('tenantName'))->toBeNull();
});

it('succeeds with closure job when a tenant is specified', function () {
    $valuestore = Valuestore::make(tempFile('tenantAware.json'))->flush();

    $currentTenant = $this->tenant;

    dispatch(function () use ($valuestore, $currentTenant) {
        $valuestore = Valuestore::make(tempFile('tenantAware.json'));

        $currentTenant->makeCurrent();

        $tenant = Tenant::current();

        $valuestore->put('tenantId', $tenant?->getKey());
        $valuestore->put('tenantName', $tenant?->name);

        $currentTenant->forget();
    });

    $this->artisan('queue:work --once');

    expect($valuestore->get('tenantId'))->toBe($this->tenant->getKey())
        ->and($valuestore->get('tenantName'))->toBe($this->tenant->name);
});
