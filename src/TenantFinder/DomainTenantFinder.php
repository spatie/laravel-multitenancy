<?php

namespace Spatie\Multitenancy\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;

class DomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request):?Tenant
    {
        $host = $request->getHost();

        return Tenant::whereDomain($host)->first();
    }
}
