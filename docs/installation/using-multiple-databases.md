---
title: Using multiple databases
weight: 3
---

Before using the following instructions, make sure you have performed [the base installation steps](/laravel-multitenancy/v1/installation/base-installation) first.

 Only use the instructions on this page if you want each of your tenants have their own database. 

## Configuring the database connections

When using separate database for each tenant, your Laravel app needs two database connections. One named `landlord`, which points to the database that should contain the `tenants` table and other system wide related info. The other connection, named `tenant` points to the database of the tenant that is considering the current tenant for a request.

In the `multitenancy` config file, you must set a name in `tenant_database_connection_name`. You can use `tenant`, but it could be any name that you want. The `landlord_database_connection_name` must also be set. A logical value could be `landlord`, but again, you could use any name you want.

Next, let's create the connections themselves. In the `database` config file, in the `connections` key you must add a database configuration for the tenant and landlord connection.

In the example below the `mysql` driver is used, but you can use any driver you'd like. For the `tenant` connection you should set the `database` to `null`. The package will dynamically set the database name depending on the tenant that's considered the current one.

```php
// in config/databases.

'connections' => [
    'tenant' => [
        'driver' => 'mysql',
        'database' => null,
        // other options such as host, username, password, ...
    ],

    'landlord' => [
        'driver' => 'mysql',
        'database' => 'name_of_landlord_db',
        // other options such as host, username, password, ...
    ],
```


### Migrating the landlord database

With the database connection set up we can migrate the landlord database. 

First, you must publish the migration file:

```bash
php artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider" --tag="migrations-multi-db"
```

The command above will publish a migration in `database/migrations/landlord` that will create the `tenants` table.

Perform this command to run that migration. The value of database option should be the landlord database connection name.

```bash
php artisan migrate --path=database/migrations/landlord --database=landlord 
```

When creating new migrations that should be performed on the landlord database, you should store them in the `database/migrations/landlord` path. After creating your own migrations, use the command above to migrate the landlord database.

### Determining the current tenant

Per request, the package can determine the "current" tenant. This is done by a `TenantFinder`. The package ships with a `DomainTenantFinder` that will make the tenant active who's `domain` attribute value matches the host of the current request.

To use that tenant finder, specify its class name in the `tenant_finder` key of the `multitenancy` config file.

```php
// in multitenancy.php
/*
 * This class is responsible for determining which tenant should be current
 * for the given request.
 *
 * This class should extend `Spatie\Multitenancy\TenantFinder\TenantFinder`
 *
 */
'tenant_finder' => Spatie\Multitenancy\TenantFinder\DomainTenantFinder::class,
```

If you want to determine the "current" tenant some other way, you can [create a custom tenant finder](/laravel-multitenancy/v1/basic-usage/automatically-determining-the-current-tenant/).

### Automatically switching to the database of the current tenant

When making a tenant the "current" one, the package will execute all tasks that are specified in the `switch_tenant_tasks` key of the `multitenancy` config file.

The package ships with a task called `SwitchTenantDatabase` to will make the tenant database connection use the database whose name is in the `database` attribute of the tenant.

You should add this task to the `switch_tenant_tasks` key.

```php
/*
 * These tasks will be performed to make a tenant current.
 *
 * A valid task is any class that implements Spatie\Multitenancy\Tasks\SwitchTenantTask
 */
'switch_tenant_tasks' => [
    Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask::class,
],
```

The package also provides [other tasks](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/overview/) that you could optionally add to `switch_tenant_tasks`. You can also [create a custom task](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/creating-your-own-task/).

### Creating tenant databases

Now that automatic database switching for tenants is configured, you can migrate the tenant databases. Because there are so many ways to go about it, the package does not handle creating databases. You should take care of creating new tenant databases in your own application code. A nice place to trigger this could be [when a `Tenant` model gets created](/laravel-multitenancy/v1/basic-usage/using-a-custom-tenant-model/#performing-actions-when-a-tenant-gets-created).

If you want to get a feel of how the package works, you could create a couple of rows in the `tenants` table, fill the `database` attribute and manually create those databases.

### Migrating tenant databases

When you want to migrate tenant databases, all future migrations should be stored in `database/migrations`.

To perform these migrations, you can use [the `tenants:migrate` command](/laravel-multitenancy/v1/advanced-usage/executing-artisan-commands-for-each-tenant). This command will loop over all rows in the `tenants` table. It will make each tenant the current one, and migrate the database.

```bash
php artisan tenants:artisan migrate
```

### Preparing models

All models in your project should either use the `UsesLandLordConnection` or `UsesTenantConnection`, depending on if the underlying table of the models lives in the landlord or tenant database.

### Next steps

When using multiple tenants, you probably want to [isolate the cache](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/cache/) or [use separate filesystems per tenant](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/filesystem/), ... These things are perform by task classes that will be executed when making a tenant the current one.

The package can also has an option to [make the queue tenant aware](/laravel-multitenancy/v1/installation/making-queues-tenant-aware/).
