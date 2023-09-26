<?php

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    config()->set('queue.default', 'database');

    $this->tenant = Tenant::factory()->create();
    $this->valuestore = Valuestore::make(tempFile('tenantAware.json'))->flush();
});

it('succeeds with closure job when queues are tenant aware by default', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->tenant->execute(function () {
        dispatch(function () {
            $valuestore = Valuestore::make(tempFile('tenantAware.json'));

            $tenant = Tenant::current();

            $valuestore->put('tenantId', $tenant?->getKey());
            $valuestore->put('tenantName', $tenant?->name);
        });
    });

    expect($this->valuestore->get('tenantId'))->toBeNull()
        ->and($this->valuestore->get('tenantName'))->toBeNull();

    $this->artisan('queue:work --once');

    expect($this->valuestore->get('tenantId'))->toBe($this->tenant->getKey())
        ->and($this->valuestore->get('tenantName'))->toBe($this->tenant->name);
});

it('fails with closure job when queues are not tenant aware by default', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

    $this->tenant->execute(function () {
        dispatch(function () {
            $valuestore = Valuestore::make(tempFile('tenantAware.json'));

            $tenant = Tenant::current();

            $valuestore->put('tenantId', $tenant?->getKey());
            $valuestore->put('tenantName', $tenant?->name);
        });
    });

    $this->artisan('queue:work --once');

    expect($this->valuestore->get('tenantId'))->toBeNull()
        ->and($this->valuestore->get('tenantName'))->toBeNull();
});

it('succeeds with closure job when a tenant is specified', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

    $this->tenant->execute(function (Tenant $currentTenant) {
        dispatch(function () use ($currentTenant) {
            $valuestore = Valuestore::make(tempFile('tenantAware.json'));

            $currentTenant->makeCurrent();

            $tenant = Tenant::current();

            $valuestore->put('tenantId', $tenant?->getKey());
            $valuestore->put('tenantName', $tenant?->name);

            $currentTenant->forget();
        });
    });

    expect($this->valuestore->get('tenantId'))->toBeNull()
        ->and($this->valuestore->get('tenantName'))->toBeNull();

    $this->artisan('queue:work --once');

    expect($this->valuestore->get('tenantId'))->toBe($this->tenant->getKey())
        ->and($this->valuestore->get('tenantName'))->toBe($this->tenant->name);
});
