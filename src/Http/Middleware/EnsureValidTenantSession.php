<?php

namespace Spatie\Multitenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidTenantSession
{
    use UsesMultitenancyConfig;

    public function handle(Request $request, Closure $next)
    {
        $sessionKey = $this->currentTenantSessionKey();

        // If the request does not have a session, we cannot verify the tenant id in the session, so we will just pass the request through.
        if (! $request->hasSession()) {
            return $next($request);
        }

        // If the session does not have the tenant id, we will set it to the current tenant id and pass the request
        if (! $request->session()->has($sessionKey)) {
            $request->session()->put($sessionKey, app($this->currentTenantContainerKey())->getKey());

            return $next($request);
        }

        // If the tenant id in the session does not match the current tenant id, we will handle the invalid tenant session
        if ($request->session()->get($sessionKey) !== app($this->currentTenantContainerKey())->getKey()) {
            return $this->handleInvalidTenantSession($request);
        }

        return $next($request);
    }

    protected function handleInvalidTenantSession(Request $request)
    {
        abort(Response::HTTP_UNAUTHORIZED);
    }
}
