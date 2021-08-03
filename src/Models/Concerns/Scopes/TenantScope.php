<?php

namespace Spatie\Multitenancy\Models\Concerns\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Spatie\Multitenancy\Models\Tenant;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! $tenant = Tenant::current()) {
            throw new NoCurrentTenant();
        }

        $builder->where($tenant->getForeignKey(), $tenant->getKey());
    }
}
