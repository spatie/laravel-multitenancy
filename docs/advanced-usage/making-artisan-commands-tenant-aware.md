---
title: Making Artisan command tenant aware
weight: 3
---

Commands can be made tenant aware by applying the `TenantAware` trait. When using the trait it is required to append `{--tenant=*}` or `{--tenant=}` to the command signature.

Caution: If you append `{--tenant=*}`, then if no `tenant` option is provided when executing the command, the command will execute for _all_ tenants.

```php
use Illuminate\Console\Command;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;

class YourFavoriteCommand extends Command
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

When your tenants each have their own database, you could migrate each tenant database with this command (given you are using a task like [`SwitchTenantDatabase`](https://docs.spatie.be/laravel-multitenancy/v4/using-tasks-to-prepare-the-environment/switching-databases)):

```bash
php artisan tenants:artisan migrate
```

We are using the `migrate` command here, but you can pass any command that you like.

If an inner command returns a non-zero exit code, `tenants:artisan` continues with the remaining tenants and exits with status `1`. It exits with status `0` when every selected tenant succeeds, or when there are no tenants and no selection was requested. An explicit selection that matches no tenants exits with status `1`.

Previously, inner command exit codes were ignored. Deploy pipelines and scheduler failure hooks will now detect those failures. Exceptions still propagate and stop the run; this behavior has not changed.

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

### Continuing when a tenant fails

By default, an exception thrown for one tenant stops the command, and the remaining tenants are skipped. That is the safe choice for commands like `migrate`, where you want to fix a failure before it is repeated for every tenant.

For commands where each tenant is independent, such as scheduled maintenance, pass `--continue-on-error`. An exception is then reported to your exception handler, the failing tenant is written to the output, and the command continues with the next tenant. It exits with a failure code if any tenant failed.

```bash
php artisan tenants:artisan "model:prune" --continue-on-error
```

Your own tenant aware commands support this too when you add `{--continue-on-error}` to their signature.

```php
protected $signature = 'your-favorite-command {--tenant=*} {--continue-on-error}';
```

### State Persistence
When using `TenantAware`, the same command instance is executed for each tenant.
This means that instance properties will retain their values between tenant executions unless explicitly reset.


```php
use Illuminate\Console\Command;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;

class YourFavoriteCommand extends Command
{
use TenantAware;

    protected $signature = 'your-favorite-command {--tenant=*}';

    protected int $counter = 0;

    public function handle()
    {
        // Reset state at the beginning of each tenant execution
        $this->counter = 0;

        // Without the reset above, $counter would start at the value 
        // from the previous tenant's execution
        $this->incrementCounter();

        return $this->line('Counter: '. $this->counter);
    }

    public function incrementCounter()
    {
        $this->counter++;
    }
}
```
