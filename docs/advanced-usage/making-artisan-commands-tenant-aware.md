---
title: Making Artisan command tenant aware
weight: 3
---

Commands can be made tenant aware by applying the `TenantAware` trait. When using the trait it is required to append `{--tenant=*}` to the command signature.
 
```php
use Illuminate\Console\Command;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;

class YourFavouriteCommand extends Command
{
    use TenantAware;

    protected $signature = 'your-favorite-command {--tenant=*}';

    public function handle()
    {
        return $this->line('The tenant is '. Tenant::current()->name);
    }
}
```

When executing the command, the `handle` method will be called for each tenant. 

```bash
php artisan your-favorite-command 
```

Using the example above, the name of each tenant will be written to the output of the command.


You can also execute the command for a specific tenant:


```bash
php artisan your-favorite-command --tenant=1
```

## Using the tenants:artisan command

If you cannot change an Artisan command yourself, for instance a command from Laravel itself or a command from a package, you can use `tenants:artisan <artisan command>`. This command will loop over tenants and for each of them make that tenant current, and execute the artisan command.

When your tenants each have their own database, you could migrate each tenant database with this command (given you are using a task like [`SwitchTenantDatabase`](https://docs.spatie.be/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/switching-databases)):

```bash
php artisan tenants:artisan migrate
```

We are using the `migrate` command here, but you can pass any command that you like.

### Passing arguments and options

If you use quotes around the command part you can use any argument and option that the command supports.

```bash
php artisan tenants:artisan "migrate --seed"
```

### Running artisan command for specific tenants

If the command only needs to run for a specific tenant, you can pass its `id` to the `tenant` option.

```bash
php artisan tenants:artisan "migrate --seed" --tenant=123
```
