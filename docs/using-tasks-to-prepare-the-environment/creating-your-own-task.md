---
title: Creating your own task
weight: 2
---

A task is any class that implements `Spatie\Multitenancy\Tasks\MakeTenantCurrentTask`. Here how that interface looks like.

```php
namespace Spatie\Multitenancy\Tasks;

use Spatie\Multitenancy\Models\Tenant;

interface MakeTenantCurrentTask
{
    public function makeCurrent(Tenant $tenant): void;
}
```

To use the tasks you should add it to the `switch_tenant_tasks` key of the `multitenancy` config file.


