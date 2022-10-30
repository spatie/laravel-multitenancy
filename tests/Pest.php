<?php

use Illuminate\Support\Facades\Schema;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

use Spatie\Multitenancy\Models\Tenant;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(Spatie\Multitenancy\Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function tenantHasDatabaseTable(Tenant $tenant, string $tableName): bool
{
    $tenant->makeCurrent();

    $tenantHasDatabaseTable = Schema::connection('tenant')->hasTable($tableName);

    Tenant::forgetCurrent();

    return $tenantHasDatabaseTable;
}

function assertTenantDatabaseHasTable(Tenant $tenant, string $tableName): void
{
    $tenantHasDatabaseTable = tenantHasDatabaseTable($tenant, $tableName);

    assertTrue(
        $tenantHasDatabaseTable,
        "Tenant database does not have table  `{$tableName}`"
    );
}

function assertTenantDatabaseDoesNotHaveTable(Tenant $tenant, string $tableName): void
{
    $tenantHasDatabaseTable = tenantHasDatabaseTable($tenant, $tableName);

    assertFalse(
        $tenantHasDatabaseTable,
        "Tenant database has unexpected table  `{$tableName}`"
    );
}

function tempFile(string $fileName): string
{
    return __DIR__ . "/temp/{$fileName}";
}
