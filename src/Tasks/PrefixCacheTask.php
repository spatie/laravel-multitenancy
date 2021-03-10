<?php

namespace Spatie\Multitenancy\Tasks;

use Spatie\Multitenancy\Models\Tenant;

class PrefixCacheTask implements SwitchTenantTask
{
    protected ?string $originalPrefix;

    public function __construct(
        private ?string $storeName = null,
        private ?string $cacheKeyBase = null
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
    }
}
