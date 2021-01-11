---
title: Determining the current tenant
weight: 4
---

Per request, the package can determine the "current" tenant. This is done by a `TenantFinder`. The package ships with a `DomainTenantFinder` that will make the tenant active whose `domain` attribute value matches the host of the current request.

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

If you want to determine the "current" tenant some other way, you can [create a custom tenant finder](/docs/laravel-multitenancy/v1/basic-usage/automatically-determining-the-current-tenant/).
