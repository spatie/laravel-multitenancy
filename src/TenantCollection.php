<?php declare(strict_types=1);

namespace Spatie\Multitenancy;

use Spatie\Multitenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

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
        $collection = $this->$operation(fn (Tenant $tenant) => $tenant->execute($callable));

        return new static($collection->items);
    }
}
