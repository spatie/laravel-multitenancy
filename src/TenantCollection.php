<?php

namespace Spatie\Multitenancy;

use Illuminate\Support\Collection;
use Spatie\Multitenancy\Models\Tenant;

class TenantCollection extends Collection
{
    public function eachCurrent(callable $callable): self
    {
        $previousCurrentTenant = Tenant::current();

        $this->each(fn (Tenant $tenant) => $callable($tenant));

        optional($previousCurrentTenant)->makeCurrent();

        return $this;
    }
}
