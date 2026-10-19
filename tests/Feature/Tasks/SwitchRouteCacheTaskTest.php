<?php

use Illuminate\Routing\Route;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchRouteCacheTask;
use Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses\RegisterTenantRoutesTask;
use Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses\SwitchRouteCacheWithoutReinitializingRouterTask;

beforeEach(function () {
    config()->set('multitenancy.switch_tenant_tasks', [SwitchRouteCacheTask::class]);
});

afterEach(function () {
    unset($_SERVER['LARAVEL_OCTANE']);

    foreach (glob(app()->bootstrapPath('cache/routes-v7*.php')) as $cachedRoutesFile) {
        unlink($cachedRoutesFile);
    }
});

it('will use a different routes cache environment variable for each tenant', function () {
    /** @var Tenant $tenant */
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))
        ->toEqual("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");

    /** @var Tenant $anotherTenant */
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

it('will use a shared routes cache environment variable for all tenants', function () {
    config()->set('multitenancy.shared_routes_cache', true);

    /** @var Tenant $tenant */
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))->toEqual("bootstrap/cache/routes-v7-tenants.php");

    /** @var Tenant $anotherTenant */
    $anotherTenant = Tenant::factory()->create();
    $anotherTenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))->toEqual("bootstrap/cache/routes-v7-tenants.php");

    Tenant::forgetCurrent();
    expect(env('APP_ROUTES_CACHE'))->toBeNull();
});

it('will restore the landlord routes when forgetting the current tenant in a long running process', function () {
    $_SERVER['LARAVEL_OCTANE'] = 1;

    bootWithLandlordRoutesCache();

    /** @var Tenant $tenant */
    $tenant = Tenant::factory()->create();
    writeRoutesCache(tenantRoutesCachePath($tenant), ['tenant-only' => fn () => 'tenant']);

    $tenant->makeCurrent();
    expect(cachedRouteUris())->toContain('tenant-only')->not->toContain('landlord-only');

    Tenant::forgetCurrent();
    expect(cachedRouteUris())->toContain('landlord-only')->not->toContain('tenant-only');
});

it('will only keep the routes of the latest tenant when switching between tenants', function () {
    bootWithLandlordRoutesCache();

    /** @var Tenant $tenant */
    $tenant = Tenant::factory()->create();
    writeRoutesCache(tenantRoutesCachePath($tenant), ['tenant-a-only' => fn () => 'tenant-a']);

    /** @var Tenant $anotherTenant */
    $anotherTenant = Tenant::factory()->create();
    writeRoutesCache(tenantRoutesCachePath($anotherTenant), ['tenant-b-only' => fn () => 'tenant-b']);

    $tenant->makeCurrent();
    expect(cachedRouteUris())->toContain('tenant-a-only')->not->toContain('tenant-b-only');

    $anotherTenant->makeCurrent();
    expect(cachedRouteUris())->toContain('tenant-b-only')->not->toContain('tenant-a-only');
});

it('will keep the landlord routes when the tenant routes cache file does not exist', function () {
    bootWithLandlordRoutesCache();

    expect(app()->routesAreCached())->toBeTrue();

    /** @var Tenant $tenant */
    $tenant = Tenant::factory()->create();

    $tenant->makeCurrent();

    expect(cachedRouteUris())->toContain('landlord-only');
});

it('will load the tenant routes cache even when the landlord routes are not cached', function () {
    expect(app()->routesAreCached())->toBeFalse();

    /** @var Tenant $tenant */
    $tenant = Tenant::factory()->create();
    writeRoutesCache(tenantRoutesCachePath($tenant), ['tenant-only' => fn () => 'tenant']);

    $tenant->makeCurrent();

    expect(cachedRouteUris())->toContain('tenant-only');
});

it('will not touch the router when the process does not reinitialize it', function () {
    config()->set('multitenancy.switch_tenant_tasks', [SwitchRouteCacheWithoutReinitializingRouterTask::class]);

    bootWithLandlordRoutesCache();

    /** @var Tenant $tenant */
    $tenant = Tenant::factory()->create();
    writeRoutesCache(tenantRoutesCachePath($tenant), ['tenant-only' => fn () => 'tenant']);

    $landlordRoutes = app('router')->getRoutes();

    $tenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))->toEqual("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");
    expect(app('router')->getRoutes())->toBe($landlordRoutes);
    expect(cachedRouteUris())->toContain('landlord-only')->not->toContain('tenant-only');

    Tenant::forgetCurrent();
    expect(env('APP_ROUTES_CACHE'))->toBeNull();
    expect(app('router')->getRoutes())->toBe($landlordRoutes);
    expect(cachedRouteUris())->toContain('landlord-only')->not->toContain('tenant-only');
});

it('will restore the uncached landlord routes when forgetting the current tenant', function () {
    expect(app()->routesAreCached())->toBeFalse();

    app('router')->get('landlord-only', fn () => 'landlord');

    /** @var Tenant $tenant */
    $tenant = Tenant::factory()->create();
    writeRoutesCache(tenantRoutesCachePath($tenant), ['tenant-only' => fn () => 'tenant']);

    $tenant->makeCurrent();
    expect(cachedRouteUris())->toContain('tenant-only')->not->toContain('landlord-only');

    Tenant::forgetCurrent();
    expect(cachedRouteUris())->toContain('landlord-only')->not->toContain('tenant-only');
});

it('restores uncached landlord routes after every tenant regardless of task order', function (array $tasks) {
    config()->set('multitenancy.switch_tenant_tasks', $tasks);

    app('router')->get('landlord-only', fn () => 'landlord');

    $tenants = Tenant::factory()->count(2)->create();

    foreach ($tenants as $tenant) {
        $tenant->makeCurrent();
        expect(cachedRouteUris())->toContain('landlord-only', 'tenant-only');

        Tenant::forgetCurrent();
        expect(cachedRouteUris())->toContain('landlord-only')->not->toContain('tenant-only');
    }
})->with([
    'route cache task first' => [[SwitchRouteCacheTask::class, RegisterTenantRoutesTask::class]],
    'route cache task last' => [[RegisterTenantRoutesTask::class, SwitchRouteCacheTask::class]],
]);

function tenantRoutesCachePath(Tenant $tenant): string
{
    return app()->basePath("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");
}

function bootWithLandlordRoutesCache(): void
{
    writeRoutesCache(app()->getCachedRoutesPath(), ['landlord-only' => fn () => 'landlord']);

    app()->forgetInstance('routes.cached');

    require app()->getCachedRoutesPath();
}

/** @return array<int, string> */
function cachedRouteUris(): array
{
    return collect(app('router')->getRoutes()->getRoutes())
        ->map(fn (Route $route) => $route->uri())
        ->all();
}
