---
title: Base installation
weight: 1
---

This package can be installed via composer:

```bash
composer require "spatie/laravel-multitenancy:^1.0"
```

### Publishing the config file

You must publish the config file:

```bash
php artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider" --tag="config"
```

This is the default content of the config file that will be published at `config/multitenancy.php`:

```php
<?php

use Spatie\Multitenancy\Actions\ForgetCurrentTenantAction;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Actions\MakeTenantCurrentAction;
use Spatie\Multitenancy\Actions\MigrateTenantAction;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\PrefixCacheTask;

return [
    /*
     * This class is responsible for determining which tenant should be the
     * current tenant for the given request.
     *
     * This class should extend `Spatie\Multitenancy\TenantFinder\TenantFinder`
     *
     */
    'tenant_finder' => null,

    /*
     * These tasks will be performed when switching tenants.
     *
     * A valid task is any class that implements Spatie\Multitenancy\Tasks\SwitchTenantTask
     */
    'switch_tenant_tasks' => [
        // add tasks here
    ],

    /*
     * This class is the model used for storing configuration of tenants.
     *
     * It must be or extend `Spatie\Multitenancy\Models\Tenant::class`
     */
    'tenant_model' => Tenant::class,

    /*
     * If there is a current tenant when dispatching a job, the id of the current tenant
     * will be automatically set on the job. When the job is executed, the set
     * tenant on the job will be made current.
     */
    'queues_are_tenant_aware_by_default' => true,

    /*
     * The connection name to reach the tenant database.
     *
     * Set to `null` to use the default connection.
     */
    'tenant_database_connection_name' => null,

    /*
     * The connection name to reach the landlord database.
     */
    'landlord_database_connection_name' => null,

    /*
     * This key will be used to bind the current tenant in the container.
     */
    'current_tenant_container_key' => 'currentTenant',

    /*
     * You can customize some of the behavior of this package by using your own custom action.
     * Your custom action should always extend the default one.
     */
    'actions' => [
        'make_tenant_current_action' => MakeTenantCurrentAction::class,
        'forget_current_tenant_action' => ForgetCurrentTenantAction::class,
        'make_queue_tenant_aware_action' => MakeQueueTenantAwareAction::class,
        'migrate_tenant' => MigrateTenantAction::class,
    ],
];
```

### Protecting against cross tenant abuse

To prevent users from a tenant abusing their session to access another tenant, you must use the `Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession` middleware on all tenant-aware routes.

If all your application routes are tenant-aware, you can add it to your global middleware in `app\Http\Kernel.php`

```php
// in `app\Http\Kernel.php`

protected $middlewareGroups = [
    'web' => [
        // ...
        \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
        \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class,
    ]
];
```

If only some routes are tenant-aware, create a new middleware group:

```php
// in `app\Http\Kernel.php`

protected $middlewareGroups = [
    // ...
    'tenant' => [
        \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
        \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class,
    ]
];
```

Then apply the group to the appropriate routes:

```php
// in a routes file

Route::middleware('tenant')->group(function() {
    // routes
});
```

This middleware will respond with an unauthorized response code (401) when the user tries to use their session to view another tenant. Make sure to include `\Spatie\Multitenancy\Http\Middleware\NeedsTenant` first, as this will [handle any cases where a valid tenant cannot be found](/docs/laravel-multitenancy/v1/advanced-usage/ensuring-a-current-tenant-has-been-set).

### Next steps

If you prefer to use just one glorious database for all your tenants, read the installation instructions for [using a single database](/docs/laravel-multitenancy/v1/installation/using-a-single-database).

If you want to use separate databases for each tenant, head over to the installation instructions for [using multiple databases](/docs/laravel-multitenancy/v1/installation/using-multiple-databases).
