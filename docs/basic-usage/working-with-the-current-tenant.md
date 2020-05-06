---
title: Determining the current tenant
weight: 3
---

You can find the current method like this.

```php
Spatie\Multitenancy\Models\Tenant::current(); // returns the current tenant, or if not tenant is current, `null`
```

A current tenant will also be bound in the container using the `currentTenant` key.

```php
app('currentTenant'); // returns the current tenant, or if not tenant is current, `null`
```
