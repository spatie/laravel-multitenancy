<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchRouteCacheTask;

beforeEach(function () {
    config()->set('multitenancy.switch_tenant_tasks', [SwitchRouteCacheTask::class]);
});

test('it will use a different routes cache environment variable for each tenant', function () {
    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();
    $this->assertEquals("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php", env('APP_ROUTES_CACHE'));

    /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
    $anotherTenant = Tenant::factory()->create();
    $anotherTenant->makeCurrent();
    $this->assertEquals("bootstrap/cache/routes-v7-tenant-{$anotherTenant->id}.php", env('APP_ROUTES_CACHE'));

    $tenant->makeCurrent();
    $this->assertEquals("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php", env('APP_ROUTES_CACHE'));

    $anotherTenant->makeCurrent();
    $this->assertEquals("bootstrap/cache/routes-v7-tenant-{$anotherTenant->id}.php", env('APP_ROUTES_CACHE'));

    Tenant::forgetCurrent();
    $this->assertNull(env('APP_ROUTES_CACHE'));
});

test('it will use a shared routes cache environment variable for all tenants', function () {
    config()->set('multitenancy.shared_routes_cache', true);

    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();
    $this->assertEquals("bootstrap/cache/routes-v7-tenants.php", env('APP_ROUTES_CACHE'));

    /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
    $anotherTenant = Tenant::factory()->create();
    $anotherTenant->makeCurrent();
    $this->assertEquals("bootstrap/cache/routes-v7-tenants.php", env('APP_ROUTES_CACHE'));

    Tenant::forgetCurrent();
    $this->assertNull(env('APP_ROUTES_CACHE'));
});
