<?php

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchRouteCacheTask;

beforeEach(function () {
    config()->set('multitenancy.switch_tenant_tasks', [SwitchRouteCacheTask::class]);
});

test('it will use a different routes cache environment variable for each tenant', function () {
    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))
        ->toEqual("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");

    /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
    $anotherTenant = Tenant::factory()->create();
    $anotherTenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))
        ->toEqual("bootstrap/cache/routes-v7-tenant-{$anotherTenant->id}.php");

    $tenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))
        ->toEqual("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");

    $anotherTenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))
        ->toEqual("bootstrap/cache/routes-v7-tenant-{$anotherTenant->id}.php");

    Tenant::forgetCurrent();
    expect(env('APP_ROUTES_CACHE'))->toBeNull();
});

test('it will use a shared routes cache environment variable for all tenants', function () {
    config()->set('multitenancy.shared_routes_cache', true);

    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))->toEqual("bootstrap/cache/routes-v7-tenants.php");

    /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
    $anotherTenant = Tenant::factory()->create();
    $anotherTenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))->toEqual("bootstrap/cache/routes-v7-tenants.php");

    Tenant::forgetCurrent();
    expect(env('APP_ROUTES_CACHE'))->toBeNull();
});
