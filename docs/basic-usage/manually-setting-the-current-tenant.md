---
title: Manually setting the current tenant
weight: 2
---

You can manually make a tenant the current one by calling `makeCurrent()` on it.

```php
$tenant->makeCurrent();
```

When a tenant is made the current one, the package will run [all tasks configured](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/overview/) in the `switch_tenant_tasks` key of the `multitenancy` config file.
