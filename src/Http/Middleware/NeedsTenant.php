<?php

namespace Spatie\Multitenancy\Http\Middleware;

use Closure;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;

class NeedsTenant
{
    public function handle($request, Closure $next)
    {
        if (! app(IsTenant::class)::checkCurrent()) {
            return $this->handleInvalidRequest();
        }

        return $next($request);
    }

    public function handleInvalidRequest()
    {
        throw NoCurrentTenant::make();
    }
}
