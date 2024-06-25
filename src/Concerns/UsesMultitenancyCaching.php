<?php

namespace Spatie\Multitenancy\Concerns;

use Illuminate\Support\Arr;
use Spatie\Multitenancy\Exceptions\InvalidConfiguration;

trait UsesMultitenancyCaching
{
    public function getTenantCacheKey(): string
    {
        return config('multitenancy.cache_key', 'multitenancy'));
    }

    public function getTenantCacheDuration(): ?int
    {
        return config('multitenancy.cache_duration'));
    }

    public function getTenantCacheStore(): mixed
    {
        return config('multitenancy.cache_store'));
    }
}
