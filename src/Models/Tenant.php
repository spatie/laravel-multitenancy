<?php

namespace Spatie\Multitenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;
use Spatie\Multitenancy\Events\MakingTenantCurrentEvent;

class Tenant extends Model
{
    protected $connection = 'landlord';

    public function makeCurrent(): self
    {
        event(new MakingTenantCurrentEvent($this));

        $this->configure();

        $this->bindAsCurrentTenant();

        event(new MadeTenantCurrentEvent($this));

        return $this;
    }

    protected function configure(): self
    {
        $this
            ->configureTenantDatabase()
            ->configureTenantCache();

        return $this;
    }

    public static function current(): ?self
    {
        if (! app()->has('current_tenant')) {
            return null;
        }

        return app('current_tenant');
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }

    protected function bindAsCurrentTenant(): void
    {
        app()->forgetInstance('current_tenant');

        app()->instance('current_tenant', $this);
    }

    protected function configureTenantDatabase(): self
    {
        config([
            'database.connections.tenant.database' => $this->getDatabaseName(),
        ]);

        DB::purge('tenant');

        return $this;
    }

    protected function configureTenantCache(): self
    {
        config()->set('cache.prefix', $this->id);

        return $this;
    }
}
