<?php

namespace Spatie\Multitenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Actions\ForgetCurrentTenantAction;
use Spatie\Multitenancy\Actions\MakeTenantCurrentAction;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\TenantCollection;

class Tenant extends Model
{
    use UsesLandlordConnection;

    public function makeCurrent(): self
    {
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

    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }
}
