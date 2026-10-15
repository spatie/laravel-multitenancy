<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Env;
use Spatie\Multitenancy\Contracts\IsTenant;

class SwitchRouteCacheTask implements SwitchTenantTask
{
    protected ?RouteCollection $uncachedLandlordRoutes = null;

    public function makeCurrent(IsTenant $tenant): void
    {
        Env::getRepository()->set('APP_ROUTES_CACHE', $this->getCachedRoutesPath($tenant));

        $this->reinitializeRouter();
    }

    public function forgetCurrent(): void
    {
        Env::getRepository()->clear('APP_ROUTES_CACHE');

        $this->reinitializeRouter();
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

    protected function reinitializeRouter(): void
    {
        if (! $this->shouldReinitializeRouter()) {
            return;
        }

        $this->uncachedLandlordRoutes ??= $this->uncachedRoutes();

        $cachedRoutesPath = app()->getCachedRoutesPath();

        if (is_file($cachedRoutesPath)) {
            require $cachedRoutesPath;

            return;
        }

        if ($this->uncachedLandlordRoutes) {
            app('router')->setRoutes($this->uncachedLandlordRoutes);
        }
    }

    protected function uncachedRoutes(): ?RouteCollection
    {
        $routes = app('router')->getRoutes();

        return $routes instanceof RouteCollection ? $routes : null;
    }
}
