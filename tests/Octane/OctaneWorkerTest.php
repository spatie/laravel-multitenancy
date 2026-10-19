<?php

use Illuminate\Queue\Events\JobProcessing;
use Laravel\Octane\Exceptions\TaskExceptionResult;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Octane\TestClasses\DisconnectingFakeClient;

beforeEach(function () {
    writeRoutesCache(app()->getCachedRoutesPath(), [
        'landlord-only' => fn () => 'landlord',
        'current' => fn () => json_encode([Tenant::current()?->id, config('database.connections.tenant.database')]),
        'queue-listeners' => fn () => (string) count(app('events')->getListeners(JobProcessing::class)),
    ]);

    $this->tenantA = Tenant::factory()->create(['domain' => 'a.test', 'database' => 'laravel_mt_tenant_1']);
    $this->tenantB = Tenant::factory()->create(['domain' => 'b.test', 'database' => 'laravel_mt_tenant_2']);

    foreach ([$this->tenantA, $this->tenantB] as $tenant) {
        writeRoutesCache(app()->basePath("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php"), [
            'tenant-only' => fn () => 'tenant',
            'current' => fn () => json_encode([Tenant::current()?->id, config('database.connections.tenant.database')]),
        ]);
    }

    $this->bootOctaneWorker(new DisconnectingFakeClient([]));
});

it('will keep the routes of a tenant out of the next landlord request on the same worker', function () {
    expect($this->handleOctaneRequest('http://landlord.test/tenant-only')->getStatusCode())->toBe(404);

    expect($this->handleOctaneRequest('http://a.test/tenant-only')->getContent())->toBe('tenant');

    expect($this->handleOctaneRequest('http://landlord.test/tenant-only')->getStatusCode())->toBe(404);
    expect($this->handleOctaneRequest('http://landlord.test/landlord-only')->getContent())->toBe('landlord');

    expect($this->handleOctaneRequest('http://a.test/landlord-only')->getStatusCode())->toBe(404);
});

it('will switch the tenant database for every request and restore the landlord afterwards', function () {
    expect($this->handleOctaneRequest('http://a.test/current')->getContent())
        ->toBe(json_encode([$this->tenantA->id, 'laravel_mt_tenant_1']));

    expect($this->handleOctaneRequest('http://b.test/current')->getContent())
        ->toBe(json_encode([$this->tenantB->id, 'laravel_mt_tenant_2']));

    expect($this->handleOctaneRequest('http://landlord.test/current')->getContent())
        ->toBe(json_encode([null, null]));
});

it('will keep the landlord routes when a tenant has no routes cache file', function () {
    unlink(app()->basePath("bootstrap/cache/routes-v7-tenant-{$this->tenantB->id}.php"));

    expect($this->handleOctaneRequest('http://b.test/landlord-only')?->getContent())->toBe('landlord')
        ->and($this->octaneClient->errors)->toBeEmpty();
});

it('will forget the tenant when the client disconnects before the response is sent', function () {
    $this->octaneClient->disconnectOnNextResponse();

    expect($this->handleOctaneRequest('http://a.test/tenant-only'))->toBeNull()
        ->and($this->octaneClient->errors)->toHaveCount(1);

    expect($this->handleOctaneRequest('http://landlord.test/tenant-only')->getStatusCode())->toBe(404);
    expect($this->handleOctaneRequest('http://landlord.test/current')->getContent())->toBe(json_encode([null, null]));
});

it('will register the queue listeners once for the whole worker', function () {
    $listeners = $this->handleOctaneRequest('http://landlord.test/queue-listeners')->getContent();

    $this->handleOctaneRequest('http://a.test/tenant-only');

    expect($this->handleOctaneRequest('http://landlord.test/queue-listeners')->getContent())->toBe($listeners);
});

it('will run the switch tenant tasks inside a concurrent task', function () {
    $tenant = $this->tenantA;

    $result = $this->octaneWorker->handleTask(function () use ($tenant) {
        $tenant->makeCurrent();

        return [
            Tenant::current()?->id,
            config('database.connections.tenant.database'),
            env('APP_ROUTES_CACHE'),
        ];
    });

    expect($result->result)->toBe([$tenant->id, 'laravel_mt_tenant_1', "bootstrap/cache/routes-v7-tenant-{$tenant->id}.php"]);

    expect(env('APP_ROUTES_CACHE'))->toBeNull();
    expect($this->handleOctaneRequest('http://landlord.test/tenant-only')->getStatusCode())->toBe(404);
    expect($this->handleOctaneRequest('http://landlord.test/current')->getContent())->toBe(json_encode([null, null]));
});

it('will restore landlord routes after a concurrent task fails', function () {
    $tenant = $this->tenantA;

    $result = $this->octaneWorker->handleTask(function () use ($tenant) {
        $tenant->makeCurrent();

        throw new RuntimeException('The concurrent task failed');
    });

    expect($result)->toBeInstanceOf(TaskExceptionResult::class);
    expect($result->getOriginal()->getMessage())->toBe('The concurrent task failed');

    expect(env('APP_ROUTES_CACHE'))->toBeNull();
    expect($this->handleOctaneRequest('http://landlord.test/tenant-only')->getStatusCode())->toBe(404);
    expect($this->handleOctaneRequest('http://landlord.test/current')->getContent())->toBe(json_encode([null, null]));
});
