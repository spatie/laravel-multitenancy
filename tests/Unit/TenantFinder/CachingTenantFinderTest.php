<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\CachingTenantFinder;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

function createMockTenant(int $id = 1): IsTenant
{
    $tenant = Mockery::mock(IsTenant::class);
    $tenant->shouldReceive('getKey')->andReturn($id);

    return $tenant;
}

function createCountingFinder(?IsTenant $tenant): TenantFinder
{
    return new class($tenant) extends TenantFinder {
        public int $callCount = 0;

        public function __construct(private ?IsTenant $tenant) {}

        public function findForRequest(Request $request): ?IsTenant
        {
            $this->callCount++;

            return $this->tenant;
        }
    };
}

function arrayCache(): Repository
{
    return new Repository(new ArrayStore());
}

it('caches the resolved tenant', function () {
    $tenant = createMockTenant();
    $innerFinder = createCountingFinder($tenant);
    $finder = new CachingTenantFinder($innerFinder, arrayCache());

    $request = Request::create('https://my-domain.com');

    expect($finder->findForRequest($request))->toBe($tenant);

    // Second call should hit cache, not the inner finder
    $finder->findForRequest($request);
    expect($innerFinder->callCount)->toBe(1);
});

it('does not cache null results', function () {
    $innerFinder = createCountingFinder(null);
    $finder = new CachingTenantFinder($innerFinder, arrayCache());

    $request = Request::create('https://unknown.com');

    expect($finder->findForRequest($request))->toBeNull();

    // Null is not cached, so inner finder is called again
    $finder->findForRequest($request);
    expect($innerFinder->callCount)->toBe(2);
});

it('can forget a cached tenant by request', function () {
    $tenant = createMockTenant();
    $innerFinder = createCountingFinder($tenant);
    $finder = new CachingTenantFinder($innerFinder, arrayCache());

    $request = Request::create('https://my-domain.com');

    $finder->findForRequest($request);
    expect($innerFinder->callCount)->toBe(1);

    $finder->forget($request);

    $finder->findForRequest($request);
    expect($innerFinder->callCount)->toBe(2);
});

it('can forget a cached tenant by key', function () {
    $tenant = createMockTenant();
    $innerFinder = createCountingFinder($tenant);
    $finder = new CachingTenantFinder($innerFinder, arrayCache());

    $request = Request::create('https://my-domain.com');

    $finder->findForRequest($request);
    expect($innerFinder->callCount)->toBe(1);

    $finder->forgetByKey('my-domain.com');

    $finder->findForRequest($request);
    expect($innerFinder->callCount)->toBe(2);
});

it('caches forever when ttl is zero', function () {
    $tenant = createMockTenant();
    $innerFinder = createCountingFinder($tenant);
    $finder = new CachingTenantFinder($innerFinder, arrayCache(), cacheTtlInSeconds: 0);

    $request = Request::create('https://my-domain.com');

    expect($finder->findForRequest($request))->toBe($tenant);

    $finder->findForRequest($request);
    expect($innerFinder->callCount)->toBe(1);
});

it('isolates cache entries per host', function () {
    $tenantA = createMockTenant(id: 1);
    $tenantB = createMockTenant(id: 2);

    $innerFinder = new class($tenantA, $tenantB) extends TenantFinder {
        public int $callCount = 0;

        public function __construct(private IsTenant $tenantA, private IsTenant $tenantB) {}

        public function findForRequest(Request $request): ?IsTenant
        {
            $this->callCount++;

            return match ($request->getHost()) {
                'a.com' => $this->tenantA,
                'b.com' => $this->tenantB,
                default => null,
            };
        }
    };

    $cache = arrayCache();
    $finder = new CachingTenantFinder($innerFinder, $cache);

    expect($finder->findForRequest(Request::create('https://a.com')))->toBe($tenantA);
    expect($finder->findForRequest(Request::create('https://b.com')))->toBe($tenantB);
    expect($innerFinder->callCount)->toBe(2);

    // Forgetting one doesn't affect the other
    $finder->forgetByKey('a.com');

    $finder->findForRequest(Request::create('https://a.com'));
    expect($innerFinder->callCount)->toBe(3);

    $finder->findForRequest(Request::create('https://b.com'));
    expect($innerFinder->callCount)->toBe(3); // Still cached
});

it('allows overriding resolveCacheKey via subclass', function () {
    $tenant = createMockTenant();
    $innerFinder = createCountingFinder($tenant);

    $finder = new class($innerFinder, arrayCache()) extends CachingTenantFinder {
        protected function resolveCacheKey(Request $request): string
        {
            return 'multitenancy.tenant_finder.' . explode('.', $request->getHost())[0];
        }
    };

    // Both requests have the same subdomain — should share a cache entry
    $finder->findForRequest(Request::create('https://tenant1.example.com'));
    $finder->findForRequest(Request::create('https://tenant1.other-domain.com'));
    expect($innerFinder->callCount)->toBe(1);

    // Different subdomain — should miss cache
    $finder->findForRequest(Request::create('https://tenant2.example.com'));
    expect($innerFinder->callCount)->toBe(2);

    // forgetByKey works with the resolved key
    $finder->forgetByKey('tenant1');

    $finder->findForRequest(Request::create('https://tenant1.example.com'));
    expect($innerFinder->callCount)->toBe(3);
});
