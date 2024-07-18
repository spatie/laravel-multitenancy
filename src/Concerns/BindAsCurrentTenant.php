<?php

namespace Spatie\Multitenancy\Concerns;

use Spatie\Multitenancy\Contracts\IsTenant;

trait BindAsCurrentTenant
{
    protected function bindAsCurrentTenant(IsTenant $tenant): static
    {
        $containerKey = config('multitenancy.current_tenant_container_key');

        app()->forgetInstance($containerKey);

        app()->instance($containerKey, $tenant);

        return $this;
    }
}
