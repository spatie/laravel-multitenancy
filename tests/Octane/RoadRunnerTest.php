<?php

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Octane\TestClasses\RoadRunnerServer;
use Spatie\Multitenancy\Tests\Octane\TestClasses\StateController;

beforeEach(function () {
    if (! is_file(RoadRunnerServer::binaryPath())) {
        $this->markTestSkipped('RoadRunner is not installed, run `vendor/bin/rr get-binary --no-config -n` to download it.');
    }

    $this->tenantA = Tenant::factory()->create(['domain' => 'a.test', 'database' => 'laravel_mt_tenant_1']);
    $this->tenantB = Tenant::factory()->create(['domain' => 'b.test', 'database' => 'laravel_mt_tenant_2']);

    writeRoutesCache(app()->getCachedRoutesPath(), ['state' => StateController::class]);
    writeRoutesCache(app()->basePath("bootstrap/cache/routes-v7-tenant-{$this->tenantA->id}.php"), [
        'state' => StateController::class,
        'tenant-only' => StateController::class,
    ]);

    $this->server = (new RoadRunnerServer())->start([
        'APP_ENV' => 'testing',
        'APP_DEBUG' => 'true',
        'APP_KEY' => config('app.key'),
        'DB_HOST' => config('database.connections.landlord.host'),
        'DB_PORT' => (string) config('database.connections.landlord.port'),
        'DB_USERNAME' => config('database.connections.landlord.username'),
        'DB_PASSWORD' => (string) config('database.connections.landlord.password'),
    ]);
});

afterEach(function () {
    if (isset($this->server)) {
        $this->server->stop();
    }

    foreach (['packages.php', 'services.php'] as $manifest) {
        @unlink(app()->bootstrapPath("cache/{$manifest}"));
    }
});

it('switches the database and the routes for a tenant request', function () {
    expect($this->server->get('a.test', 'state')->json())->toMatchArray([
        'tenant' => 'a.test',
        'database' => 'laravel_mt_tenant_1',
        'routes_cache' => "bootstrap/cache/routes-v7-tenant-{$this->tenantA->id}.php",
    ]);

    expect($this->server->get('a.test', 'tenant-only')->status())->toBe(200);
});

it('restores the landlord environment for the next request served by the same worker', function () {
    $this->server->get('a.test', 'tenant-only');

    expect($this->server->get('landlord.test', 'state')->json())->toMatchArray([
        'tenant' => null,
        'database' => null,
        'routes_cache' => null,
    ]);

    expect($this->server->get('landlord.test', 'tenant-only')->status())->toBe(404);
});

it('serves a tenant without a route cache file with the landlord routes', function () {
    $this->server->get('a.test', 'tenant-only');

    expect($this->server->get('b.test', 'state')->json())->toMatchArray([
        'tenant' => 'b.test',
        'database' => 'laravel_mt_tenant_2',
        'routes_cache' => "bootstrap/cache/routes-v7-tenant-{$this->tenantB->id}.php",
    ]);

    expect($this->server->get('b.test', 'tenant-only')->status())->toBe(404);
});
