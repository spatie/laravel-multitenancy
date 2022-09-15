<?php

namespace Spatie\Multitenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DetermineTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! config('multitenancy.tenant_finder')) {
            return;
        }

        $tenantFinder = app(TenantFinder::class);
        $tenant = $tenantFinder->findForRequest($request);
        optional($tenant)->makeCurrent();

        return $next($request);
    }
}
