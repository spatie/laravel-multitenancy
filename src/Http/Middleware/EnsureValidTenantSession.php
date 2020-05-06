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
        $sessionKey = 'ensure_valid_tenant_session_tenant_id';

        if (! $request->session()->has($sessionKey)) {
            $request->session()->put($sessionKey, app($this->currentTenantContainerKey())->id);

            return $next($request);
        }

        if ($request->session()->get($sessionKey) !== app($this->currentTenantContainerKey())->id) {
            return $this->handleInvalidTenantSession($request);
        }

        return $next($request);
    }

    protected function handleInvalidTenantSession($request)
    {
        abort(Response::HTTP_UNAUTHORIZED);
    }
}
