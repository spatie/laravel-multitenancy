<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\DomainTenantFinder;

beforeEach(function () {
    $this->tenantFinder = new DomainTenantFinder();
    config()->set('multitenancy.tenant_finder_manual_cache', true);

    $store = config('multitenancy.domain_cache.store', 'global');

    try {
        $this->cache = Cache::store($store);
    } catch (\Throwable) {
        $this->cache = Cache::store();
    }
});

it('does not cache when tenant is null', function () {
    $request = Request::create('https://non-existent-domain.test');
    $cacheKey = config('multitenancy.domain_cache.prefix', 'tenant_by_domain:') . 'non-existent-domain.test';

    $result = $this->tenantFinder->findForRequest($request);

    expect($result)->toBeNull()
        ->and($this->cache->has($cacheKey))->toBeFalse();
});

it('returns cached tenant directly when cache has the domain key', function () {
    $mockTenant = Mockery::mock(Tenant::class, IsTenant::class);
    $cacheKey = config('multitenancy.domain_cache.prefix', 'tenant_by_domain:') . 'cached-domain.test';

    $this->cache->forever($cacheKey, $mockTenant);

    $request = Request::create('https://cached-domain.test');
    $result = $this->tenantFinder->findForRequest($request);

    expect($result)->toBe($mockTenant);

    $this->cache->forget($cacheKey);
});
