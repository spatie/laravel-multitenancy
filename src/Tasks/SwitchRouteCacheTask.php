<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Support\Env;
use Spatie\Multitenancy\Models\Tenant;

class SwitchRouteCacheTask implements SwitchTenantTask
{
    public function makeCurrent(Tenant $tenant): void
    {
        Env::getRepository()->set('APP_ROUTES_CACHE', $this->getCachedRoutesPath($tenant));

        if (app()->routesAreCached() && $this->shouldReinitializeRouter()) {
            // Laravel Octane will load the routes cache only once when initializing the application.
            // To undo this and reload the proper route cache based of `APP_ROUTES_CACHE`, we need to reinitialize the `Router`.

            require app()->getCachedRoutesPath();
        }
    }

    public function forgetCurrent(): void
    {
        Env::getRepository()->clear('APP_ROUTES_CACHE');
    }

    protected function getCachedRoutesPath(Tenant $tenant): string
    {
        if (config('multitenancy.shared_routes_cache')) {
            return "bootstrap/cache/routes-v7-tenants.php";
        }

        return "bootstrap/cache/routes-v7-tenant-{$tenant->id}.php";
    }

    protected function shouldReinitializeRouter(): bool
    {
        return isset($_SERVER['LARAVEL_OCTANE'])
            || app()->runningInConsole()
            || app()->runningUnitTests();
    }
}
