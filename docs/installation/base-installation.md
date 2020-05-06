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
use Spatie\Multitenancy\Models\Tenant;

return [
    /*
     * This class is responsible for determining which tenant should be current
     * for the given request.
     *
     * This class should extend `Spatie\Multitenancy\TenantFinder\TenantFinder`
     *
     */
    'tenant_finder' => null,

    /*
     * These tasks will be performed to make a tenant current.
     *
     * A valid task is any class that implements Spatie\Multitenancy\Tasks\MakeTenantCurrentTask
     */
    'make_tenant_current_tasks' => [

    ],
    
    /*
     * This class is the model used for storing configuration on tenants.
     *
     * It must be or extend `Spatie\Multitenancy\Models\Tenant::class`
     */
    'tenant_model' => Tenant::class,

    /*
     * If there is a current tenant when dispatching a job, the id of the current tenant
     * will be automatically set on the job. When the job is executed, the set
     * tenant on the job will be made current.
     */
    'tenant_aware_queue' => true,

    /*
     * The connection name to reach the a tenant database
     */
    'tenant_database_connection_name' => 'default',

    /*
     * The connection name to reach the a landlord database
     */
    'landlord_database_connection_name' => 'default',
];
```

### Protecting against cross tenant abuse

To prevent users from a tenant abusing their session to access another tenant you must use the `Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession` on all routes.

Add it to your global middleware in `app\Http\Kernel.php`

```php
// in `app\Http\Kernel.php`

protected $middleware = [
    // ...
    \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class
];
```

This middleware will respond with an unauthorized response code (401) when the user tries use its session to view another tenant.

### Next steps

If you prefer to use just one glorious database for all your tenants, read the installation instructions for [using a single database](/laravel-multitenancy/v1/installation/using-a-single-database). 

If you want to use separate databases for each tenant, head over to the installation instructions for [using multiple databases](/laravel-multitenancy/v1/installation/using-multiple-databases). 


