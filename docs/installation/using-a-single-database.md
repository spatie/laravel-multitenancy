---
title: Using a single database
weight: 2
---

Before using the following instructions, make sure you have performed [the base installation steps](/laravel-multitenancy/v1/installation/base-installation) first.

### Migrating the database

With the database connection set up we can migrate the landlord database. 

First, you must publish and run the migration:

```bash
php artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider" --tag="migrations"
php artisan migrate
```

This will create the `tenants` table which holds configuration per tenant.

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

### Next steps

When using multiple tenants, you probably want to [isolate the cache](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/cache/) or [use separate filesystems per tenant](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/filesystems/), ... These things are perform by [task classes](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/overview/) that will be executed when making a tenant the current one.

The package can also has an option to [make the queue tenant aware](/laravel-multitenancy/v1/installation/making-queues-tenant-aware/).
