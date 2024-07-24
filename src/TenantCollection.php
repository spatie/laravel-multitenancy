<?php

namespace Spatie\Multitenancy;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Multitenancy\Contracts\IsTenant;

class TenantCollection extends Collection
{
    public function eachCurrent(callable $callable): static
    {
        return $this->performCollectionMethodWhileMakingTenantsCurrent(
            operation: 'each',
            callable: $callable
        );
    }

    public function filterCurrent(callable $callable): static
    {
        return $this->performCollectionMethodWhileMakingTenantsCurrent(
            operation: 'filter',
            callable: $callable
        );
    }

    public function mapCurrent(callable $callable): static
    {
        return $this->performCollectionMethodWhileMakingTenantsCurrent(
            operation: 'map',
            callable: $callable
        );
    }

    public function rejectCurrent(callable $callable): static
    {
        return $this->performCollectionMethodWhileMakingTenantsCurrent(
            operation: 'reject',
            callable: $callable
        );
    }

    protected function performCollectionMethodWhileMakingTenantsCurrent(string $operation, callable $callable): static
    {
        $collection = $this->$operation(fn (IsTenant $tenant) => $tenant->execute($callable));

        return new static($collection->items);
    }
}
