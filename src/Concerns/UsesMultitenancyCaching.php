<?php

namespace Spatie\Multitenancy\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Multitenancy\Exceptions\InvalidConfiguration;

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
                ->rememberForever($cacheKey, fn () => getTenantCollection());
        }

        cache()
            ->store($cacheStore)
            ->remember($cacheKey, $cacheDuration, fn () => getTenantCollection());
    }

    protected function getTenantCollection(): Collection
    {
        $tenantModelClass = config('multitenancy.tenant_model');

        return app($tenantModelClass)::all();
    }
    
    protected function getTenantCacheKey(): string
    {
        return config('multitenancy.cache_key', 'multitenancy'));
    }

    protected function getTenantCacheDuration(): ?int
    {
        return config('multitenancy.cache_duration'));
    }

    protected function getTenantCacheStore(): mixed
    {
        return config('multitenancy.cache_store'));
    }
}
