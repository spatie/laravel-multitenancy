<?php

namespace Spatie\Multitenancy;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Multitenancy\Models\Tenant;

class TenantCollection extends Collection
{
    public function eachCurrent(callable $callable): self
    {
        return $this->performCollectionMethodWhileMakingTenantsCurrent(
            operation: 'each',
            callable: $callable
        );
    }

    public function mapCurrent(callable $callable): self
    {
        return $this->performCollectionMethodWhileMakingTenantsCurrent(
            operation: 'map',
            callable: $callable
        );
    }

    protected function performCollectionMethodWhileMakingTenantsCurrent(string $operation, callable $callable): self
    {
        $collection = $this->$operation(fn (Tenant $tenant) => $tenant->execute($callable));

        return new static($collection->items);
    }
}
