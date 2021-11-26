<?php

namespace Spatie\Multitenancy\Tasks;

class SwitchRouteCacheTask implements SwitchTenantTask
{
    public function makeCurrent(Tenant $tenant): void
    {
        Env::getRepository()->set('APP_ROUTES_CACHE', "bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");
    }

    public function forgetCurrent(): void
    {
        Env::getRepository()->clear('APP_ROUTES_CACHE');
    }
}
