<?php

namespace Spatie\Multitenancy\Concerns;

use Illuminate\Support\Arr;
use Spatie\Multitenancy\Exceptions\InvalidConfiguration;

trait UsesMultitenancyCaching
{
    public function getTenantCacheKey(): array
    {
        return config('multitenancy.cache_key', 'multitenancy'));
    }

    public function getTenantCacheDuration(): ?int
    {
        return config('multitenancy.cache_duration'));
    }

    public function getTenantCacheStore(): array
    {
        return config('multitenancy.cache_store', 'multitenancy'));
    }
}
