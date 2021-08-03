<?php

namespace Spatie\Multitenancy\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\Scopes\TenantScope;

/** @mixin Model */
trait BelongsToTenant
{
    public static function bootedBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('multitenancy.tenant_model'));
    }
}


