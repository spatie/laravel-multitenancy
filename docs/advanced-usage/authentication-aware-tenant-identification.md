---
title: Authentication aware tenant identification
weight: 10
---

Some projects will have their tenant based on a user attribute.

By default, determining a tenant will happen at the very beginning of a request, even before routes and authentication are done. Utilize the tenantFinder at a later stage in the request by applying the `\Spatie\Multitenancy\Http\Middleware\DetermineTenant` middleware on those routes where authentication has already happened.

## Base Installation

Ensure the steps in [base installation](/docs/laravel-multitenancy/v2/installation/base-installation) have been completed with the fact that only some routes will be tenant aware in mind.

## Create relation between users and tenant

To identify a tenant from the User model, create a relation between the two.

Create relation migration.

```bash
php artisan make:migration add_tenant_column_to_users
```

In the new migration file, create a relation between users and tenants.

```php
use Spatie\Multitenancy\Models\Tenant;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(Tenant::class)->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
```

In users model, create eloquent relation indicating users belong to tenant.

```php
// in `app\Models\Users.php`
use Spatie\Multitenancy\Models\Tenant;

...

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

...
```

## Create custom tenant finder

Create a tenant finder that returns the current tenant. Ensure it returns early if the user is not logged in.

```php
\\ In `app/Http/TenantFinder.php`
namespace App\Http;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder as MultitenancyTenantFinder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;

use Illuminate\Http\Request;

class TenantFinder extends MultitenancyTenantFinder
{
    use UsesTenantModel;

    public function findForRequest(Request $request): ?Tenant
    {
        $tenantId = optional(auth()->user())->tenant_id;
        if (!$tenantId) {
            return null; // No tenant, no need to search db.
        }
        $tenant = $this->getTenantModel()->find($tenantId);
        return $tenant;
    }
}

```

Configure tenant finder in multitenancy configuration.

```php
\\ In `app/Http/TenantFinder.php`

...

    'tenant_finder' => \App\Http\TenantFinder::class,

...

```


## Add the middleware to tenant group

Update tenant middleware group to include determination of tenant.

```php
// in `app\Http\Kernel.php`

protected $middlewareGroups = [
    // ...
    'tenant' => [
        \Spatie\Multitenancy\Http\Middleware\DetermineTenant::class,
        \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class
    ]
];
```

Ensure the tenant is determined prior to declaring need for tenant. Remove the `EnsureValidTenantSession` middleware, as your sessions are not separated by tenant, instead being dependent upon laravel authentication.