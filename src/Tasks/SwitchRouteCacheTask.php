<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Env;
use Spatie\Multitenancy\Contracts\IsTenant;

class SwitchRouteCacheTask implements SwitchTenantTask
{
    protected ?RouteCollection $uncachedLandlordRoutes = null;

    public function __construct()
    {
        if (! $this->shouldReinitializeRouter()) {
            return;
        }

        $this->uncachedLandlordRoutes = $this->uncachedRoutes();
    }

    public function makeCurrent(IsTenant $tenant): void
    {
        Env::getRepository()->set('APP_ROUTES_CACHE', $this->getCachedRoutesPath($tenant));

        $this->reinitializeRouter();
    }

    public function forgetCurrent(): void
    {
        Env::getRepository()->clear('APP_ROUTES_CACHE');

        if ($this->uncachedLandlordRoutes) {
            $this->restoreUncachedLandlordRoutes();

            return;
        }

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

        $cachedRoutesPath = app()->getCachedRoutesPath();

        if (! is_file($cachedRoutesPath)) {
            return;
        }

        require $cachedRoutesPath;
    }

    protected function restoreUncachedLandlordRoutes(): void
    {
        app('router')->setRoutes(clone $this->uncachedLandlordRoutes);
    }

    protected function uncachedRoutes(): ?RouteCollection
    {
        $routes = app('router')->getRoutes();

        return $routes instanceof RouteCollection ? clone $routes : null;
    }
}
