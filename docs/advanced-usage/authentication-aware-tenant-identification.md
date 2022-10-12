---
title: Authentication aware tenant identification
weight: 10
---

Some projects will have their tenant based on a user attribute.

On every request determining a tenant will happen at the very beginning of a request, even before routes and authentication are done. To utilize the tenantFinder after authentication, create and prioritize a middleware to determine tenant.

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

// ...

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

// ...
```

## Create custom tenant finder

Create a tenant finder that returns the current tenant. Ensure it returns early if the user is not logged in.

```php
\\ In `app/Http/AuthenticatedTenantFinder.php`
namespace App\Http;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder as MultitenancyTenantFinder;

class AuthenticatedTenantFinder extends MultitenancyTenantFinder
{
    use UsesTenantModel;

    public function __construct(
        protected Guard $authGuard
    ) {}

    public function findForRequest(Request $request): ?Tenant
    {
        $tenantId = $this->authGuard->user()?->tenant_id;
        if (is_null($tenantId))
            return null; // No tenant, no need to search db.
        $tenant = $this->getTenantModel()->find($tenantId);
        return $tenant;
    }
}
```

Configure tenant finder in multitenancy configuration.

```php
// In `app/Http/TenantFinder.php`

// ...
    'tenant_finder' => \App\Http\AuthenticatedTenantFinder::class,
// ...

```

## Create DetermineTenant middleware

Create new middleware so that you can control when the determine tenant action happens.

```php
// in `app/Http/Middleware/DetermineTenant`
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Multitenancy;

class DetermineTenant
{
    public function __construct(
        protected Multitenancy $multitenancy
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $this->multitenancy->determineCurrentTenant($request);
        return $next($request);
    }
}
```

## Add the middleware to tenant group

Update tenant middleware group to include determination of tenant.

```php
// in `app\Http\Kernel.php`

protected $middlewareGroups = [
    // ...
    'tenant' => [
        \App\Http\Middleware\DetermineTenant::class,
        \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class
    ]
];
```

Ensure the tenant is determined prior to declaring need for tenant. Remove the `EnsureValidTenantSession` middleware, as your sessions are not separated by tenant, instead being dependent upon laravel authentication.
