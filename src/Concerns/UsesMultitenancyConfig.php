<?php

namespace Spatie\Multitenancy\Concerns;

use Illuminate\Support\Arr;
use Spatie\Multitenancy\Exceptions\InvalidConfiguration;
use Spatie\Multitenancy\Models\Tenant;

trait UsesMultitenancyConfig
{
    public function tenantDatabaseConnectionName(): ?string
    {
        return config('multitenancy.tenant_database_connection_name') ?? config('database.default');
    }

    public function landlordDatabaseConnectionName(): ?string
    {
        return config('multitenancy.landlord_database_connection_name') ?? config('database.default');
    }

    public function currentTenantContextKey(): string
    {
        return config('multitenancy.current_tenant_context_key');
    }

    public function currentTenantContainerKey(): string
    {
        return config('multitenancy.current_tenant_container_key');
    }

    public function getMultitenancyActionClass(string $actionName, string $actionClass)
    {
        $configuredClass = config("multitenancy.actions.{$actionName}") ?? $actionClass;

        if (! is_a($configuredClass, $actionClass, true)) {
            throw InvalidConfiguration::invalidAction(
                actionName: $actionName,
                configuredClass: $configuredClass ?? '',
                actionClass: $actionClass
            );
        }

        return app($configuredClass);
    }

    public function getTenantArtisanSearchFields(): array
    {
        return Arr::wrap(config('multitenancy.tenant_artisan_search_fields'));
    }

    public function getDedectedDatabaseConnectionName(): ?string
    {
        return Tenant::checkCurrent()
            ? $this->tenantDatabaseConnectionName()
            : $this->landlordDatabaseConnectionName();
    }
}
