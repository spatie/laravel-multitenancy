<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Laravel\Octane\OctaneServiceProvider;

use function Orchestra\Testbench\default_skeleton_path;

use Spatie\Multitenancy\MultitenancyServiceProvider;
use Spatie\Multitenancy\Tasks\SwitchRouteCacheTask;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\TenantFinder\DomainTenantFinder;
use Spatie\Multitenancy\Tests\Octane\TestClasses\StateController;

return Application::configure(basePath: default_skeleton_path())
    ->withProviders([OctaneServiceProvider::class, MultitenancyServiceProvider::class])
    ->withRouting(using: fn () => Route::get('state', StateController::class))
    ->withMiddleware()
    ->withExceptions()
    ->booting(function (Application $app) {
        $connection = $app['config']->get('database.connections.mysql');

        $app['config']->set([
            'database.default' => 'landlord',
            'database.connections.landlord' => [
                ...$connection,
                'database' => 'laravel_mt_landlord',
            ],
            'database.connections.tenant' => [
                ...$connection,
                'database' => null,
            ],
            'multitenancy.tenant_finder' => DomainTenantFinder::class,
            'multitenancy.landlord_database_connection_name' => 'landlord',
            'multitenancy.tenant_database_connection_name' => 'tenant',
            'multitenancy.switch_tenant_tasks' => [
                SwitchRouteCacheTask::class,
                SwitchTenantDatabaseTask::class,
            ],
        ]);
    })
    ->create();
