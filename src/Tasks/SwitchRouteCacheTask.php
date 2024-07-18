<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Support\Env;
use Spatie\Multitenancy\Contracts\IsTenant;

class SwitchRouteCacheTask implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        Env::getRepository()->set('APP_ROUTES_CACHE', $this->getCachedRoutesPath($tenant));

        if (app()->routesAreCached() && $this->shouldReinitializeRouter()) {
            require app()->getCachedRoutesPath();
        }
    }

    public function forgetCurrent(): void
    {
        Env::getRepository()->clear('APP_ROUTES_CACHE');
    }

    protected function getCachedRoutesPath(IsTenant $tenant): string
    {
        if (config('multitenancy.shared_routes_cache')) {
            return "bootstrap/cache/routes-v7-tenants.php";
        }

        return "bootstrap/cache/routes-v7-tenant-{$tenant->getKey()}.php";
    }

    protected function shouldReinitializeRouter(): bool
    {
        return isset($_SERVER['LARAVEL_OCTANE'])
            || app()->runningInConsole()
            || app()->runningUnitTests();
    }
}
