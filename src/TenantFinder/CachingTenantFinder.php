<?php

namespace Spatie\Multitenancy\TenantFinder;

use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\InvalidatesTenantCache;
use Spatie\Multitenancy\Contracts\IsTenant;

class CachingTenantFinder extends TenantFinder implements InvalidatesTenantCache
{
    public function __construct(
        protected TenantFinder $finder,
        protected Repository $cache,
        protected int $cacheTtlInSeconds = 300,
    ) {}

    public function findForRequest(Request $request): ?IsTenant
    {
        $cacheKey = $this->resolveCacheKey($request);

        $cached = $this->cache->get($cacheKey);

        if ($cached instanceof IsTenant) {
            return $cached;
        }

        $tenant = $this->finder->findForRequest($request);

        if (! $tenant) {
            return null;
        }

        $this->cacheTtlInSeconds === 0
            ? $this->cache->forever($cacheKey, $tenant)
            : $this->cache->put($cacheKey, $tenant, $this->cacheTtlInSeconds);

        return $tenant;
    }

    public function forget(Request $request): bool
    {
        return $this->cache->forget($this->resolveCacheKey($request));
    }

    public function forgetByKey(string $key): bool
    {
        return $this->cache->forget($this->cachePrefix() . $key);
    }

    protected function resolveCacheKey(Request $request): string
    {
        return $this->cachePrefix() . $request->getHost();
    }

    protected function cachePrefix(): string
    {
        return 'multitenancy.tenant_finder.';
    }
}
