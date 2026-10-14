<?php

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchRouteCacheTask;
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

function writeRoutesCache(string $path, string $uri): void
{
    $route = new Route(['GET'], $uri, ['uses' => 'TenantsController@index', 'as' => $uri]);
    $route->prepareForSerialization();

    $routes = new RouteCollection();
    $routes->add($route);

    $compiledRoutes = var_export($routes->compile(), true);

    file_put_contents($path, "<?php\n\napp('router')->setCompiledRoutes({$compiledRoutes});\n");
}

function tenantRoutesCachePath(Tenant $tenant): string
{
    return app()->basePath("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");
}

function bootWithLandlordRoutesCache(): void
{
    writeRoutesCache(app()->getCachedRoutesPath(), 'landlord-only');

    app()->forgetInstance('routes.cached');

    require app()->getCachedRoutesPath();
}

function cachedRouteUris(): array
{
    return collect(app('router')->getRoutes()->getRoutes())
        ->map(fn (Route $route) => $route->uri())
        ->all();
}

it('will use a different routes cache environment variable for each tenant', function () {
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

it('will use a shared routes cache environment variable for all tenants', function () {
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

it('will restore the landlord routes when forgetting the current tenant in a long running process', function () {
    $_SERVER['LARAVEL_OCTANE'] = 1;

    bootWithLandlordRoutesCache();

    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();
    writeRoutesCache(tenantRoutesCachePath($tenant), 'tenant-only');

    $tenant->makeCurrent();
    expect(cachedRouteUris())->toContain('tenant-only')->not->toContain('landlord-only');

    Tenant::forgetCurrent();
    expect(cachedRouteUris())->toContain('landlord-only')->not->toContain('tenant-only');
});

it('will only keep the routes of the latest tenant when switching between tenants', function () {
    bootWithLandlordRoutesCache();

    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();
    writeRoutesCache(tenantRoutesCachePath($tenant), 'tenant-a-only');

    /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
    $anotherTenant = Tenant::factory()->create();
    writeRoutesCache(tenantRoutesCachePath($anotherTenant), 'tenant-b-only');

    $tenant->makeCurrent();
    expect(cachedRouteUris())->toContain('tenant-a-only')->not->toContain('tenant-b-only');

    $anotherTenant->makeCurrent();
    expect(cachedRouteUris())->toContain('tenant-b-only')->not->toContain('tenant-a-only');
});

it('will keep the landlord routes when the tenant routes cache file does not exist', function () {
    bootWithLandlordRoutesCache();

    expect(app()->routesAreCached())->toBeTrue();

    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();

    $tenant->makeCurrent();

    expect(cachedRouteUris())->toContain('landlord-only');
});

it('will load the tenant routes cache even when the landlord routes are not cached', function () {
    expect(app()->routesAreCached())->toBeFalse();

    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();
    writeRoutesCache(tenantRoutesCachePath($tenant), 'tenant-only');

    $tenant->makeCurrent();

    expect(cachedRouteUris())->toContain('tenant-only');
});

it('will not touch the router when the process does not reinitialize it', function () {
    config()->set('multitenancy.switch_tenant_tasks', [SwitchRouteCacheWithoutReinitializingRouterTask::class]);

    bootWithLandlordRoutesCache();

    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();
    writeRoutesCache(tenantRoutesCachePath($tenant), 'tenant-only');

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
