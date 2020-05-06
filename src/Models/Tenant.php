<?php

namespace Spatie\Multitenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;
use Spatie\Multitenancy\Events\MakingTenantCurrentEvent;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\Tasks\MakeTenantCurrentTask;
use Spatie\Multitenancy\UsesMultitenancyConfig;

class Tenant extends Model
{
    use UsesLandlordConnection;

    public function makeCurrent(): self
    {
        event(new MakingTenantCurrentEvent($this));

        $this->configure();

        $this->bindAsCurrentTenant();

        event(new MadeTenantCurrentEvent($this));

        return $this;
    }

    protected function makeTenantCurrentTasks(): Collection
    {
        return collect(config('multitenancy.make_tenant_current_tasks'));
    }

    protected function configure(): self
    {
        $this->makeTenantCurrentTasks()
            ->map(fn (string $taskClassName) => app($taskClassName))
            ->each(fn (MakeTenantCurrentTask $task) => $task->makeCurrent($this));

        return $this;
    }

    public static function current(): ?self
    {
        $containerKey = config('multitenancy.current_tenant_container_key');

        if (! app()->has($containerKey)) {
            return null;
        }

        return app($containerKey);
    }

    public static function checkCurrent(): bool
    {
        return static::current() !== null;
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }

    protected function bindAsCurrentTenant(): void
    {
        $containerKey = config('multitenancy.current_tenant_container_key');

        app()->forgetInstance($containerKey);

        app()->instance($containerKey, $this);
    }
}
