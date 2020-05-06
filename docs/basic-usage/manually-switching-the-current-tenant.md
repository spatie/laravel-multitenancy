---
title: Manually switching the current tenant
weight: 2
---

You can manually make a tenant the current one by calling `makeCurrent()` on it.

```php
$tenant->makeCurrent();
```

When a tenant is made the current one, the package will run [all tasks configured](TODO: link to overview) in the `make_tenant_current_tasks` key of the `multitenancy` config file.
