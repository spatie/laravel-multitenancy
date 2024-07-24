<?php

namespace Spatie\Multitenancy\Models\Concerns;

use Spatie\Multitenancy\Actions\ForgetCurrentTenantAction;
use Spatie\Multitenancy\Actions\MakeTenantCurrentAction;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantCollection;

trait ImplementsTenant
{
    public function makeCurrent(): static
    {
        if ($this->isCurrent()) {
            return $this;
        }

        static::forgetCurrent();

        $this
            ->getMultitenancyActionClass(
                actionName: 'make_tenant_current_action',
                actionClass: MakeTenantCurrentAction::class
            )
            ->execute($this);

        return $this;
    }

    public function forget(): static
    {
        $this
            ->getMultitenancyActionClass(
                actionName: 'forget_current_tenant_action',
                actionClass: ForgetCurrentTenantAction::class
            )
            ->execute($this);

        return $this;
    }

    public static function current(): ?static
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

    public function isCurrent(): bool
    {
        return static::current()?->getKey() === $this->getKey();
    }

    public static function forgetCurrent(): ?static
    {
        return tap(static::current(), fn (?IsTenant $tenant) => $tenant?->forget());
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }

    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }

    public function execute(callable $callable): mixed
    {
        $originalCurrentTenant = static::current();

        $this->makeCurrent();

        return tap($callable($this), static function () use ($originalCurrentTenant) {
            $originalCurrentTenant
                ? $originalCurrentTenant->makeCurrent()
                : static::forgetCurrent();
        });
    }

    public function callback(callable $callable): \Closure
    {
        return fn () => $this->execute($callable);
    }
}
