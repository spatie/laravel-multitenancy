<?php

namespace Spatie\Multitenancy\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;

abstract class TenantFinder
{
    abstract public function findForRequest(Request $request): ?IsTenant;
}
