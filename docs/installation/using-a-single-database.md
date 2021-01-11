---
title: Using a single database
weight: 2
---

Before using the following instructions, make sure you have performed [the base installation steps](/docs/laravel-multitenancy/v1/installation/base-installation) first.

 Only use the instructions on this page if you want to use one database.

### Migrating the database

With the database connection set up, we can migrate the landlord database.

First, you must publish and run the migration:

```bash
php artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider" --tag="migrations"
php artisan migrate --path=database/migrations/landlord
```

This will create the `tenants` table which holds configuration per tenant.

### Next steps

When using multiple tenants, you probably want to [isolate the cache](/docs/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/prefixing-cache/) or use your own separated filesystems per tenant, ... These things are performed by [task classes](/docs/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/overview/) that will be executed when making a tenant the current one.

The package also has an option to [make the queue tenant aware](/docs/laravel-multitenancy/v1/basic-usage/making-queues-tenant-aware/).
