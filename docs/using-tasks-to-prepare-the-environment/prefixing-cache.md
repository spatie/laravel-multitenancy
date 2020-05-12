---
title: Prefixing cache
weight: 5
---

You might want to use separate caches for each different tenant. The `Spatie\Multitenancy\Tasks\PrefixCacheTask` allows you to do just that. This task only works for memory based caches, such as APC and Redis.

To use this task, you should add it to the `switch_tenant_tasks` key in the `multitenancy` config file.

```php
// in config/multitenancy.php

'switch_tenant_tasks' => [
    \Spatie\Multitenancy\Tasks\PrefixCacheTask::class,
    // other tasks
],
```

When this task is installed, cache will behave like this

```php
cache()->put('key', 'original-value');

$tenant->makeCurrent();
cache('key') // returns null;
cache()->put('key', 'value-for-tenant');

$anotherTenant->makeCurrent();
cache('key') // returns null;
cache()->put('key', 'value-for-another-tenant');

Tenant::forgetCurrent();
cache('key') // returns 'original-value';

$tenant->makeCurrent();
cache('key') // returns 'value-for-tenant'

$anotherTenant->makeCurrent();
cache('key') // returns 'value-for-another-tenant'
```

Behind the scenes, this works by dynamically changing the `cache.prefix` in the `cache` config file whenever another tenant is made current.

If you want to make the cache tenant aware in another way, you should [create your own task](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/creating-your-own-task/). You can take a look at the source code of `PrefixCacheTask` for inspiration.
