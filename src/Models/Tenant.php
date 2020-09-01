<?php

namespace Spatie\Multitenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\TenantCollection;
use Spatie\Multitenancy\Actions\MakeTenantCurrentAction;
use Spatie\Multitenancy\Actions\ForgetCurrentTenantAction;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Tenant extends Model
{
    use UsesLandlordConnection;

    public function makeCurrent(): self
    {
        if ($this->isCurrent()) {
            return $this;
        }

        static::forgetCurrent();

        $this
            ->getMultitenancyActionClass('make_tenant_current_action', MakeTenantCurrentAction::class)
            ->execute($this);

        return $this;
    }

    public function forget(): self
    {
        $this
            ->getMultitenancyActionClass('forget_current_tenant_action', ForgetCurrentTenantAction::class)
            ->execute($this);

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

    public function isCurrent(): bool
    {
        return optional(static::current())->id === $this->id;
    }

    public static function forgetCurrent(): ?Tenant
    {
        $currentTenant = static::current();

        if (is_null($currentTenant)) {
            return null;
        }

        $currentTenant->forget();

        return $currentTenant;
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }

    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }

    public function execute(callable $callable)
    {
        $originalCurrentTenant = Tenant::current();

        $this->makeCurrent();

        return tap($callable($this), static function () use ($originalCurrentTenant) {
            $originalCurrentTenant
                ? $originalCurrentTenant->makeCurrent()
                : Tenant::forgetCurrent();
        });
    }
}
