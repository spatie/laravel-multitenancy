<?php

namespace Spatie\Multitenancy;

use Illuminate\Support\Collection;
use Spatie\Multitenancy\Models\Tenant;

class TenantCollection extends Collection
{
    public function eachCurrent(callable $callable): self
    {
        $previousCurrentTenant = Tenant::current();

        $this->each(function (Tenant $tenant) use ($callable) {
            $tenant->makeCurrent();

            $callable($tenant);
        });

        $previousCurrentTenant
            ? $previousCurrentTenant->makeCurrent()
            : Tenant::forgetCurrent();

        return $this;
    }

    public function mapCurrent(callable $callable): self
    {
        $previousCurrentTenant = Tenant::current();

        $newCollection = $this->map(function (Tenant $tenant) use ($callable) {
            $tenant->makeCurrent();

            return $callable($tenant);
        });

        $previousCurrentTenant
            ? $previousCurrentTenant->makeCurrent()
            : Tenant::forgetCurrent();

        return $newCollection;
    }
}
