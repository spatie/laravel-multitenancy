---
title: Using a single database
weight: 2
---

Before using the following instructions, make sure you have performed [the base installation steps](/laravel-multitenancy/v1/installation/base-installation) first.
 
 Only use the instructions on this page if you want use one database.

### Migrating the database

With the database connection set up we can migrate the landlord database. 

First, you must publish and run the migration:

```bash
php artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider" --tag="migrations"
php artisan migrate
```

This will create the `tenants` table which holds configuration per tenant.

### Next steps

When using multiple tenants, you probably want to [isolate the cache](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/cache/) or [use separate filesystems per tenant](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/filesystems/), ... These things are perform by [task classes](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/overview/) that will be executed when making a tenant the current one.

The package also has an option to [make the queue tenant aware](/laravel-multitenancy/v1/basic-usage/making-queues-tenant-aware/).
