---
title: Using a custom tenant model
weight: 6
---

If you want to change or add behaviour on the `Tenant` model you can use your custom model. There are two ways of doing this by extending the `Tenant` model provided by the package, or by prepping a model of your own.

## Option 1: extending the `Tenant` model provided by the package


Make sure that your custom model extends the `Spatie\Multitenancy\Models\Tenant` model provided by the package.

You should specify the class name of your model in the `tenant_model` key of the `multitenancy` config file.

```php
/*
 * This class is the model used for storing configuration on tenants.
 *
 * It must be or extend `Spatie\Multitenancy\Models\Tenant::class`
 */
'tenant_model' => \App\Models\CustomTenantModel::class,
```

## Option 2: using a model of your own

You don't have to extend our `Tenant` model. For example if you use Laravel Jetstream, then you probably want to use `Team` model provided by that package as your tenant model.

To accomplish that, you can implement the `IsTenant` interface and use trait `ImplementsTenant` to fulfill that interface. 

Here's an example:

```php
namespace App\Models;

use Laravel\Jetstream\Team as JetstreamTeam;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Concerns\ImplementsTenant;

class Team extends JetstreamTeam implements IsTenant
{
    use HasFactory;
    use UsesLandlordConnection;
    use ImplementsTenant;
}
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
