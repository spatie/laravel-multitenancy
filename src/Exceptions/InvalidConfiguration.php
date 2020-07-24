<?php

namespace Spatie\Multitenancy\Exceptions;

use Exception;

class InvalidConfiguration extends Exception
{
    /** @test */
    public static function tenantConnectionDoesNotExist(string $expectedConnectionName): self
    {
        return new static("Could not find a tenant connection named `{$expectedConnectionName}`. Make sure to create a connection with that name in the `connections` key of the `database` config file.");
    }

    /** @test */
    public static function tenantConnectionIsEmptyOrEqualsToLandlordConnection(): self
    {
        return new static("`SwitchTenantDatabaseTask` fails because `multitenancy.tenant_database_connection_name` is `null` or equals to `multitenancy.tenant_database_connection_name`.");
    }

    public static function invalidAction(string $actionName, string $configuredClass, string $actionClass): self
    {
        return new static("The class currently specified in the `multitenancy.actions.{$actionName}` key '{$configuredClass}' should be or extend `{$actionClass}`.");
    }
}
