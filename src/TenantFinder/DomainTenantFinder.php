<?php

namespace Spatie\Multitenancy\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Concerns\UsesMultitenancyCaching;
use Spatie\Multitenancy\Models\Tenant;

class DomainTenantFinder extends TenantFinder
{
    use UsesMultitenancyCaching;
    use UsesTenantModel;

    public function findForRequest(Request $request): ?Tenant
    {
        $host = $request->getHost();

        if ($this->getTenantCacheStore() === false) {
            return $this->findTenantByDomain($domain);
        }

        $cache = $this->getTenantCache();

        return $cache->firstWhere('domain', $host) ?? $this->findTenantByDomain($domain);
    }

    protected function findTenantByDomain(string $domain): ?Tenant
    {
        return $this->getTenantModel()::firstWhere('domain', $domain);
    }
}
