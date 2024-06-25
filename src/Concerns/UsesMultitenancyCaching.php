<?php

namespace Spatie\Multitenancy\Concerns;

use Illuminate\Support\Collection;

trait UsesMultitenancyCaching
{
    protected function createTenantCache(): void
    {
        $cacheStore = $this->getTenantCacheStore();

        if ($cacheStore === false) {
            return;
        }

        $cacheKey = $this->getTenantCacheKey();

        $cacheDuration = $this->getTenantCacheDuration();

        if ($cacheDuration === null) {
            cache()
                ->store($cacheStore)
                ->rememberForever($cacheKey, fn () => $this->getTenantCollection());

            return;
        }

        cache()
            ->store($cacheStore)
            ->remember($cacheKey, $cacheDuration, fn () => $this->getTenantCollection());
    }

    protected function clearTenantCache(): void
    {
        cache()
            ->store($this->getTenantCacheStore())
            ->delete($this->getTenantCacheKey());
    }

    protected function getTenantCache(): Collection
    {
        return cache()
            ->store($this->getTenantCacheStore())
            ->get($this->getTenantCacheKey(), collect());
    }

    protected function getTenantCollection(): Collection
    {
        $tenantModelClass = config('multitenancy.tenant_model');

        return app($tenantModelClass)::all();
    }

    protected function getTenantCacheKey(): string
    {
        return config('multitenancy.cache_key', 'multitenancy');
    }

    protected function getTenantCacheDuration(): ?int
    {
        return config('multitenancy.cache_duration');
    }

    protected function getTenantCacheStore(): mixed
    {
        return config('multitenancy.cache_store');
    }
}
