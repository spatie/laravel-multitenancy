<?php

namespace Spatie\Multitenancy\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\Models\Tenant;

class DomainTenantFinder extends TenantFinder
{
    use UsesTenantModel;

    public function findForRequest(Request $request): ?Tenant
    {
        $host = $request->getHost();

        return $this->getTenantModel()::whereDomain($host)->first();
    }
}
