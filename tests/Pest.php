<?php

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
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
uses(Spatie\Multitenancy\Tests\OctaneTestCase::class)->in('Octane');

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

/** @param array<string, string|Closure> $routes */
function writeRoutesCache(string $path, array $routes): void
{
    $routeCollection = new RouteCollection();

    foreach ($routes as $uri => $action) {
        $route = new Route(['GET'], $uri, ['uses' => $action, 'as' => $uri]);
        $route->prepareForSerialization();

        $routeCollection->add($route);
    }

    $compiledRoutes = var_export($routeCollection->compile(), true);

    file_put_contents($path, "<?php\n\napp('router')->setCompiledRoutes({$compiledRoutes});\n");
}
