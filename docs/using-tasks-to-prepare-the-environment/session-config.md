---
title: Session config
weight: 4
---

You might want to manage sessions per tenant. In this case, you can use `Spatie\Multitenancy\Tasks\SwitchSessionConfigTask`.

To use this task, as for others, you should add it to the `switch_tenant_tasks` key in the `multitenancy` config file.

```php
// in config/multitenancy.php

'switch_tenant_tasks' => [
    \Spatie\Multitenancy\Tasks\SwitchSessionConfigTask::class,
    // other tasks
],
```

When working with database session driver, you should be aware of the following.

1. The Landlord environment will use the default session driver. So, if that default driver is set to `database`, you should copy the session migration to the `database/migrations/landlord` folder and migrate it. With this done, when no tenant is set, Laravel will use the Landlord environment to manage sessions.


2. You probably may not want that complexity. You can set the session default driver to `file` so that no database table will be needed for sessions when no tenant is set. Then, you can add a key in your `config/session.php` file.

```php
[
    // other config
    'tenant_driver' => 'database',
],
```

After this has been done, whenever a tenant is set, the database connection defined for tenants will be used for sessions too.

### Notes

You should add the `Spatie\Multitenancy\Tasks\SwitchSessionConfigTask` after the `Spatie\Multitenancy\Tasks\SwitchDatabaseTask` if you are using database session driver. You may have guessed why. Because, the `Spatie\Multitenancy\Tasks\SwitchDatabaseTask` will auto-set the database connection to use.

Instead of adding the `tenant_driver` key in the session configuration file, you can also add a `session_driver` field to your custom tenant model (in your migration too). When defined, il will be used in priority. In this case, each tenant can use his convenient driver.
