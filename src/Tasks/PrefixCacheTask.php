<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Support\Facades\Cache;
use Spatie\Multitenancy\Models\Tenant;

class PrefixCacheTask implements SwitchTenantTask
{
    protected ?string $originalPrefix;

    public function __construct(
        protected ?string $storeName = null,
        protected ?string $cacheKeyBase = null
    ) {
        $this->originalPrefix = config('cache.prefix');

        $this->storeName ??= config('cache.default');

        $this->cacheKeyBase ??= 'tenant_id_';
    }

    public function makeCurrent(Tenant $tenant): void
    {
        $this->setCachePrefix($this->cacheKeyBase . $tenant->id);
    }

    public function forgetCurrent(): void
    {
        $this->setCachePrefix($this->originalPrefix);
    }

    protected function setCachePrefix(string $prefix)
    {
        config()->set('cache.prefix', $prefix);

        app('cache')->forgetDriver($this->storeName);

        // This is important because the `CacheManager` will have the `$app['config']` array cached
        // with old prefixes on the `cache` instance. Simply calling `forgetDriver` only removes
        // the `$store` but doesn't update the `$app['config']`.
        app()->forgetInstance('cache');

        Cache::clearResolvedInstances();
    }
}
