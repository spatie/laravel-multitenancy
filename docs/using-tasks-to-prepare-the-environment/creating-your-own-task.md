---
title: Creating your own task
weight: 2
---

A task is any class that implements `Spatie\Multitenancy\Tasks\SwitchTenantTask`. Here how that interface looks like.

```php
namespace Spatie\Multitenancy\Tasks;

use Spatie\Multitenancy\Models\Tenant;

interface SwitchTenantTask
{
    public function makeCurrent(Tenant $tenant): void;

    public function forgetCurrent(): void;
}
```

The `makeCurrent` function will be called when making a tenant current. A common thing to do whould be to dynamically change some configuration values.

`forgetCurrent` will be called when forgetting a tenant. This function should restore the original environment. An important thing to note is that `SwitchTenantTask` are singletons, so you could store the original values as a property and reach for them later.

Here is an example implementation where we are going to use a prefix when a tenant is current, and clear out that prefix when forgetting the tenant.


```php
namespace Spatie\Multitenancy\Tasks;

use Spatie\Multitenancy\Models\Tenant;

class PrefixCacheTask implements SwitchTenantTask
{
    protected ?string $originalPrefix;

    public function __construct()
    {
        $this->originalPrefix = config('cache.prefix');
    }

    public function makeCurrent(Tenant $tenant): void
    {
        $this->setCachePrefix("tenant_{$tenant->id}");
    }

    public function forgetCurrent(): void
    {
        $this->setCachePrefix($this->originalPrefix);
    }

    protected function setCachePrefix(string $prefix): void
    {
        config()->set('cache.prefix', $prefix);

        $storeName = config('cache.default');

        app('cache')->forgetDriver($storeName);
    }
}
```

## Registering a task

After creating a task, you must register it by putting its class name in the `switch_tenant_tasks` key of the `multitenancy` config file.

## Accepting parameters

Classes that implement `SwitchTenantTask` can accept parameters from the TODO

