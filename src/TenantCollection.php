<?php

namespace Spatie\Multitenancy;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Multitenancy\Models\Tenant;

class TenantCollection extends Collection
{
    public function eachCurrent(callable $callback): self
    {
        return $this->performCollectionMethodWhileMakingTenantsCurrent(
            operation: 'each',
            callback: $callback
        );
    }

    public function filterCurrent(callable $callback): self
    {
        return $this->performCollectionMethodWhileMakingTenantsCurrent(
            operation: 'filter',
            callback: $callback
        );
    }

    public function mapCurrent(callable $callback): self
    {
        return $this->performCollectionMethodWhileMakingTenantsCurrent(
            operation: 'map',
            callback: $callback
        );
    }

    public function rejectCurrent(callable $callback): self
    {
        return $this->performCollectionMethodWhileMakingTenantsCurrent(
            operation: 'reject',
            callback: $callback
        );
    }

    protected function performCollectionMethodWhileMakingTenantsCurrent(string $operation, callable $callback): self
    {
        $callbackWithTenant = fn (Tenant $tenant) => $tenant->execute($callback);

        $collection = $this->map($callbackWithTenant);

        return new static($collection->items);
    }
}
