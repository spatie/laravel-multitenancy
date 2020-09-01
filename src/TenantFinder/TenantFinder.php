<?php

namespace Spatie\Multitenancy\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;

abstract class TenantFinder
{
    abstract public function findForRequest(Request $request): ?Tenant;
}
