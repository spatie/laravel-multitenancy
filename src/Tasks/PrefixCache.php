<?php

namespace Spatie\Multitenancy\Tasks;

use Spatie\Multitenancy\Models\Tenant;

class PrefixCache implements MakeTenantCurrentTask
{
    public function makeCurrent(Tenant $tenant): void
    {
        config()->set('cache.prefix', $tenant->id);

        app('cache')->forgetDriver(
            config('cache.default')
        );
    }
}
