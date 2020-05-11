<?php

namespace Spatie\Multitenancy;

use Illuminate\Support\Collection;
use Spatie\Multitenancy\Models\Tenant;

class TenantCollection extends Collection
{
    public function eachCurrent(callable $callable): self
    {
        return $this->performCollectionMethodWhileMakingTenantsCurrent('each', $callable);
    }

    public function mapCurrent(callable $callable): self
    {
        return $this->performCollectionMethodWhileMakingTenantsCurrent('map', $callable);
    }

    protected function performCollectionMethodWhileMakingTenantsCurrent(string $operation, callable $callable)
    {
        $originalCurrentTenant = Tenant::current();

        $collection = $this->map(function (Tenant $tenant) use ($callable) {
            $tenant->makeCurrent();

            return $callable($tenant);
        });

        $originalCurrentTenant
            ? $originalCurrentTenant->makeCurrent()
            : Tenant::forgetCurrent();

        return $collection;
    }
}
