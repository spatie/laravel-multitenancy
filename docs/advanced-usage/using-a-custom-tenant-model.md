---
title: Using a custom tenant model
weight: 6
---

If you want to change or add behaviour on the `Tenant` model you can use your custom model. Make sure that your custom model extends the `Spatie\Multitenancy\Models\Tenant` model provided by the package.

You should specify the class name of your model in the `tenant_model` key of the `multitenancy` config file.

```php
/*
 * This class is the model used for storing configuration on tenants.
 *
 * It must be or extend `Spatie\Multitenancy\Models\Tenant::class`
 */
'tenant_model' => \App\Models\CustomTenantModel::class,
```

## Performing actions when a tenant gets created

You can leverage Eloquent's lifecycle callbacks to execute extra logic when a tenant gets created, updated, deleted, ...

Here's an example on how you could call some logic that creates a database when a tenant gets created.

```php
namespace App\Models\Tenant;

use Spatie\Multitenancy\Models\Tenant;

class CustomTenantModel extends Tenant
{
    protected static function booted()
    {
        static::creating(fn(CustomTenantModel $model) => $model->createDatabase());
    }

    public function createDatabase()
    {
        // add logic to create database
    }
}
```
