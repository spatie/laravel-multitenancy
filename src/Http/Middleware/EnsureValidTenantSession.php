<?php

namespace Spatie\Multitenancy\Http\Middleware;

use Closure;
use Spatie\Multitenancy\UsesMultitenancyConfig;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidTenantSession
{
    use UsesMultitenancyConfig;

    public function handle($request, Closure $next)
    {
        

        if (! $request->session()->has('tenant_id')) {
            $request->session()->put('tenant_id', app($this->currentTenantContainerKey())->id);

            return $next($request);
        }

        if ($request->session()->get('tenant_id') !== app($this->currentTenantContainerKey())->id) {
            return $this->handleInvalidTenantSession($request);
        }

        return $next($request);
    }

    protected function handleInvalidTenantSession($request)
    {
        abort(Response::HTTP_UNAUTHORIZED);
    }
}
