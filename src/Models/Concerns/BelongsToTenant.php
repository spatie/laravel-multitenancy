<?php

namespace Spatie\Multitenancy\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Spatie\Multitenancy\Models\Concerns\Scopes\TenantScope;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Multitenancy;

/** @mixin Model */
trait BelongsToTenant
{
    public static function bootedBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model) {
            $tenantForeignKey = app(Multitenancy::class)
                ->getTenantModel()
                ->getForeignKey();

            $model->{$tenantForeignKey} ??= Tenant::current()?->getKey();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('multitenancy.tenant_model'));
    }
}


