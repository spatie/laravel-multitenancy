---
title: Switching databases
weight: 3
---

The `Spatie\Multitenancy\Tasks\SwitchDatabaseTask` can switch the configured database name of the `tenant` database connection. The database name used will be in the `database` attribute of the `Tenant` model.

To use this task, you should add it to the `switch_tenant_tasks` key in the `multitenancy` config file.

```php
// in config/multitenancy.php

'switch_tenant_tasks' => [
    \Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask::class,
    // other tasks
],
```

If you want to change other database connection properties beside the database name, you should [create your own task](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/creating-your-own-task/). You can take a look at the source code of `SwitchTenantDatabaseTask` for inspiration.
