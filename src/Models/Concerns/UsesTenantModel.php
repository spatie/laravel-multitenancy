<?php

namespace Spatie\Multitenancy\Models\Concerns;

use Spatie\Multitenancy\Models\Tenant;

trait UsesTenantModel
{
    public function getTenantModel(): Tenant
    {
        $tenantModelClass = config('multitenancy.tenant_model');

        return new $tenantModelClass();
    }
}
