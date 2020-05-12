<?php

namespace Spatie\Multitenancy;

use Illuminate\Database\Eloquent\Collection;
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

    protected function performCollectionMethodWhileMakingTenantsCurrent(string $operation, callable $callable): self
    {
        $originalCurrentTenant = Tenant::current();

        $collection = $this->map(function (Tenant $tenant) use ($callable) {
            $tenant->makeCurrent();

            return $callable($tenant);
        });

        $originalCurrentTenant
            ? $originalCurrentTenant->makeCurrent()
            : Tenant::forgetCurrent();

        return new self($collection);
    }
}
