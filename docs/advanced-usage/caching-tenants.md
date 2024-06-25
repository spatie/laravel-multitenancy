---
title: Caching tenants
weight: 10
---

When using many tenants, each tenant retrieval can have a performance impact on the database.
It is possible to cache the tenants to reduce the number of requests to the database.

To configure the cache store to use, or to disable caching, change the value of the `cache_store` in the `multitenancy` config file.

## Using cache

To build the tenant cache using the configured store:

```bash
php artisan tenants:cache
```

To clear the tenant cache using the configured store:

```bash
php artisan tenants:refresh
```
