<?php

namespace Spatie\Multitenancy\Concerns;

use Spatie\Multitenancy\Models\Tenant;

trait BindAsCurrentTenant
{
    protected function bindAsCurrentTenant(Tenant $tenant): self
    {
        $containerKey = config('multitenancy.current_tenant_container_key');

        app()->forgetInstance($containerKey);

        app()->instance($containerKey, $tenant);

        return $this;
    }
}
